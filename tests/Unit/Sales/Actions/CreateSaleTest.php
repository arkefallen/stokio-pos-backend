<?php

namespace Tests\Unit\Sales\Actions;

use App\Common\Exceptions\StockInsufficiencyException;
use App\Modules\Auth\Models\User;
use App\Modules\Catalog\Models\Product;
use App\Modules\Sales\Actions\CreateSale;
use App\Modules\Sales\Models\Sale;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateSaleTest extends TestCase
{
    use RefreshDatabase;

    protected CreateSale $action;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->action = new CreateSale();
        $this->user = User::factory()->create();
    }

    public function test_create_sale_deducts_stock(): void
    {
        $product = Product::factory()->create(['stock_qty' => 10, 'price' => 100]);
        // Force stock ensuring it's set
        $product->forceFill(['stock_qty' => 10])->save();

        $data = [
            'payment_method' => Sale::METHOD_CASH,
            'cash_given' => 200,
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 2,
                ],
            ],
        ];

        $sale = $this->action->execute($data, $this->user->id);

        $this->assertDatabaseHas('sales', ['id' => $sale->id]);
        $this->assertEquals(8, $product->fresh()->stock_qty);
        $this->assertEquals(200, $sale->total_amount);
    }

    public function test_throws_exception_on_insufficient_stock(): void
    {
        $product = Product::factory()->create(['stock_qty' => 5]);
        $product->forceFill(['stock_qty' => 5])->save();

        $data = [
            'payment_method' => Sale::METHOD_CASH,
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 10,
                ],
            ],
        ];

        $this->expectException(StockInsufficiencyException::class);
        $this->action->execute($data, $this->user->id);
    }

    public function test_calculates_change_correctly(): void
    {
        $product = Product::factory()->create(['price' => 50, 'stock_qty' => 10]);
        $product->forceFill(['stock_qty' => 10])->save();

        $data = [
            'payment_method' => Sale::METHOD_CASH,
            'cash_given' => 100, // Total is 50
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 1,
                ],
            ],
        ];

        $sale = $this->action->execute($data, $this->user->id);

        $this->assertEquals(50, $sale->total_amount);
        $this->assertEquals(50, $sale->change_return);
        $this->assertEquals(Sale::PAYMENT_PAID, $sale->payment_status);
    }
}
