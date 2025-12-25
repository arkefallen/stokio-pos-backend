<?php

namespace Tests\Unit\Purchasing\Actions;

use App\Modules\Auth\Models\User;
use App\Modules\Catalog\Models\Product;
use App\Modules\Purchasing\Actions\CreatePurchaseOrder;
use App\Modules\Purchasing\Models\PurchaseOrder;
use App\Modules\Purchasing\Models\Supplier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreatePurchaseOrderTest extends TestCase
{
    use RefreshDatabase;

    protected CreatePurchaseOrder $action;

    protected function setUp(): void
    {
        parent::setUp();
        $this->action = new CreatePurchaseOrder();
    }

    public function test_create_po_with_items(): void
    {
        $supplier = Supplier::factory()->create();
        $user = User::factory()->create();
        $product1 = Product::factory()->create();
        $product2 = Product::factory()->create();

        $data = [
            'supplier_id' => $supplier->id,
            'expected_delivery_date' => now()->addDays(7)->toDateString(),
            'notes' => 'Urgent order',
            'items' => [
                [
                    'product_id' => $product1->id,
                    'quantity' => 10,
                    'unit_cost' => 100.00,
                ],
                [
                    'product_id' => $product2->id,
                    'quantity' => 5,
                    'unit_cost' => 50.00,
                ],
            ],
        ];

        $po = $this->action->execute($data, $user->id);

        // Assert PO created
        $this->assertDatabaseHas('purchase_orders', [
            'id' => $po->id,
            'supplier_id' => $supplier->id,
            'status' => PurchaseOrder::STATUS_PENDING,
            'created_by' => $user->id,
            'notes' => 'Urgent order',
        ]);

        // Assert items created
        $this->assertCount(2, $po->items);
        $this->assertEquals(1250.00, $po->total); // (10 * 100) + (5 * 50) = 1000 + 250 = 1250

        // Check first item
        $this->assertDatabaseHas('purchase_order_items', [
            'purchase_order_id' => $po->id,
            'product_id' => $product1->id,
            'quantity' => 10,
            'unit_cost' => 100.00,
            'subtotal' => 1000.00,
        ]);
    }

    public function test_generates_unique_po_number(): void
    {
        $supplier = Supplier::factory()->create();
        $user = User::factory()->create();
        $product = Product::factory()->create();

        $data = [
            'supplier_id' => $supplier->id,
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 1,
                    'unit_cost' => 10,
                ]
            ],
        ];

        // First PO
        $po1 = $this->action->execute($data, $user->id);

        // Second PO
        $po2 = $this->action->execute($data, $user->id);

        $this->assertNotNull($po1->purchase_number);
        $this->assertNotNull($po2->purchase_number);
        $this->assertNotEquals($po1->purchase_number, $po2->purchase_number);

        // Verify format P0-YYYYMMDD-XXXX
        $date = now()->format('Ymd');
        $this->assertStringContainsString("PO-{$date}-", $po1->purchase_number);
    }
}
