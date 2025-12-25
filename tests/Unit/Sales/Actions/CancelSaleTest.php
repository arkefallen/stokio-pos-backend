<?php

namespace Tests\Unit\Sales\Actions;

use App\Modules\Auth\Models\User;
use App\Modules\Catalog\Models\Product;
use App\Modules\Sales\Actions\CancelSale;
use App\Modules\Sales\Models\Sale;
use App\Modules\Sales\Models\SaleItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CancelSaleTest extends TestCase
{
    use RefreshDatabase;

    protected CancelSale $action;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->action = new CancelSale();
        $this->user = User::factory()->create();
    }

    public function test_cancel_restores_stock(): void
    {
        $product = Product::factory()->create(['stock_qty' => 10]);
        $product->forceFill(['stock_qty' => 10])->save();

        $sale = Sale::factory()->create();
        SaleItem::factory()->create([
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'quantity' => 5,
        ]);

        $this->action->execute($sale, $this->user->id);

        $this->assertEquals(Sale::STATUS_CANCELLED, $sale->fresh()->status);
        $this->assertEquals(15, $product->fresh()->stock_qty); // 10 + 5
    }

    public function test_cannot_cancel_already_cancelled(): void
    {
        $sale = Sale::factory()->cancelled()->create();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Sale is already cancelled.");

        $this->action->execute($sale, $this->user->id);
    }
}
