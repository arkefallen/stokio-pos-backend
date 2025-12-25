<?php

namespace Tests\Feature\Sales;

use App\Modules\Auth\Models\User;
use App\Modules\Catalog\Models\Product;
use App\Modules\Sales\Models\Sale;
use App\Modules\Sales\Models\SaleItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SaleTest extends TestCase
{
    use RefreshDatabase;

    protected string $endpoint = '/api/sales';

    public function test_list_sales(): void
    {
        Sale::factory()->count(3)->create();
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->getJson($this->endpoint)
            ->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    public function test_create_sale_successful(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['stock_qty' => 10, 'price' => 100]);
        $product->forceFill(['stock_qty' => 10])->save();

        Sanctum::actingAs($user);

        $data = [
            'payment_method' => 'cash',
            'cash_given' => 200,
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 2,
                ],
            ],
        ];

        $response = $this->postJson($this->endpoint, $data);

        $response->assertStatus(201)
            ->assertJsonPath('data.total_amount', 200)
            ->assertJsonPath('data.payment_status', 'paid');

        $this->assertDatabaseHas('sales', ['payment_method' => 'cash']);
        $this->assertEquals(8, $product->fresh()->stock_qty);
    }

    public function test_create_validation_insufficient_stock(): void
    {
        $user = User::factory()->create();
        // Stock 1
        $product = Product::factory()->create(['stock_qty' => 1]);
        $product->forceFill(['stock_qty' => 1])->save();

        Sanctum::actingAs($user);

        $data = [
            'payment_method' => 'cash',
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 5, // Request 5
                ],
            ],
        ];

        $response = $this->postJson($this->endpoint, $data);

        $response->assertStatus(422)
            ->assertJsonFragment(['message' => "Insufficient stock for product '{$product->name}'. Available: 1, Requested: 5"]);
    }

    public function test_create_validation_invalid_payment_method(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson($this->endpoint, [
            'payment_method' => 'bitcoin', // Invalid
            'items' => [],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['payment_method', 'items']);
    }

    public function test_show_sale_details(): void
    {
        $sale = Sale::factory()->create();
        SaleItem::factory()->create(['sale_id' => $sale->id]);

        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson("{$this->endpoint}/{$sale->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $sale->id)
            ->assertJsonCount(1, 'data.items');
    }

    public function test_cancel_sale_successful(): void
    {
        $product = Product::factory()->create(['stock_qty' => 10]);
        $product->forceFill(['stock_qty' => 10])->save();

        $sale = Sale::factory()->create();
        SaleItem::factory()->create([
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'quantity' => 5,
        ]);

        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson("{$this->endpoint}/{$sale->id}/cancel");

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'cancelled');

        $this->assertEquals(15, $product->fresh()->stock_qty);
    }
}
