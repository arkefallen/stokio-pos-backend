<?php

namespace Tests\Unit\Purchasing\Models;

use App\Modules\Catalog\Models\Product;
use App\Modules\Purchasing\Models\PurchaseOrder;
use App\Modules\Purchasing\Models\PurchaseOrderItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseOrderItemTest extends TestCase
{
    use RefreshDatabase;

    public function test_belongs_to_purchase_order(): void
    {
        $po = PurchaseOrder::factory()->create();
        $item = PurchaseOrderItem::factory()->create(['purchase_order_id' => $po->id]);

        $this->assertInstanceOf(PurchaseOrder::class, $item->purchaseOrder);
        $this->assertEquals($po->id, $item->purchaseOrder->id);
    }

    public function test_belongs_to_product(): void
    {
        $product = Product::factory()->create();
        $item = PurchaseOrderItem::factory()->create(['product_id' => $product->id]);

        $this->assertInstanceOf(Product::class, $item->product);
        $this->assertEquals($product->id, $item->product->id);
    }

    public function test_casts(): void
    {
        $item = PurchaseOrderItem::factory()->create([
            'unit_cost' => 10.50,
            'subtotal' => 21.00,
        ]);

        // Note: decimal cast might return string or float depending on driver, 
        // but Laravel 8+ usually returns string for decimal to preserve precision, or float if configured.
        // Let's assert equality loosely first or strict checked against known behaviour.

        $this->assertEquals(10.50, $item->unit_cost);
        $this->assertEquals(21.00, $item->subtotal);
    }
}
