<?php

namespace Tests\Feature\Purchasing;

use App\Modules\Auth\Models\User;
use App\Modules\Catalog\Models\Product;
use App\Modules\Purchasing\Models\PurchaseOrder;
use App\Modules\Purchasing\Models\Supplier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PurchaseOrderTest extends TestCase
{
    use RefreshDatabase;

    protected string $endpoint = '/api/purchase-orders';

    public function test_list_purchase_orders(): void
    {
        PurchaseOrder::factory()->count(3)->create();
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->getJson($this->endpoint)
            ->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    public function test_create_purchase_order_successfully(): void
    {
        $admin = User::factory()->admin()->create();
        $supplier = Supplier::factory()->create();
        $product = Product::factory()->create();
        Sanctum::actingAs($admin);

        $data = [
            'supplier_id' => $supplier->id,
            'expected_delivery_date' => now()->addWeeks(1)->toDateString(),
            'notes' => 'Test Order',
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 10,
                    'unit_cost' => 50.00,
                ],
            ],
        ];

        $response = $this->postJson($this->endpoint, $data);

        $response->assertStatus(201)
            ->assertJsonPath('data.supplier.id', $supplier->id)
            ->assertJsonPath('data.status', 'pending');

        $this->assertDatabaseHas('purchase_orders', ['supplier_id' => $supplier->id]);
        $this->assertDatabaseHas('purchase_order_items', ['product_id' => $product->id, 'quantity' => 10]);
    }

    public function test_create_validation_empty_items(): void
    {
        $admin = User::factory()->admin()->create();
        $supplier = Supplier::factory()->create();
        Sanctum::actingAs($admin);

        $data = [
            'supplier_id' => $supplier->id,
            'items' => [],
        ];

        $this->postJson($this->endpoint, $data)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['items']);
    }

    public function test_show_purchase_order_includes_items(): void
    {
        $po = PurchaseOrder::factory()->create();
        // create items via relationship or factory with PO id
        // Factory for items handles this, but here we attach them
        // Better to use factory directly
        // WAIT: PurchaseOrderItem factory needs explicit creation if not handled in PO factory 'afterCreating' or similar. 
        // My PO factory doesn't create items by default.

        // Create 2 items
        \App\Modules\Purchasing\Models\PurchaseOrderItem::factory()->count(2)->create(['purchase_order_id' => $po->id]);

        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson("{$this->endpoint}/{$po->id}");

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data.items');
    }

    public function test_receive_purchase_order(): void
    {
        $admin = User::factory()->admin()->create();
        $po = PurchaseOrder::factory()->ordered()->create();

        // Ensure products exist for items to update stock
        $product = Product::factory()->create(['stock_qty' => 0]);
        \App\Modules\Purchasing\Models\PurchaseOrderItem::factory()->create([
            'purchase_order_id' => $po->id,
            'product_id' => $product->id,
            'quantity' => 10
        ]);

        Sanctum::actingAs($admin);

        $response = $this->postJson("{$this->endpoint}/{$po->id}/receive");

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'received');

        $this->assertEquals(10, $product->fresh()->stock_qty);
    }

    public function test_cancel_purchase_order(): void
    {
        $admin = User::factory()->admin()->create();
        $po = PurchaseOrder::factory()->create(['status' => 'pending']);
        Sanctum::actingAs($admin);

        $response = $this->postJson("{$this->endpoint}/{$po->id}/cancel");

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'cancelled');
    }
}
