<?php

namespace Tests\Unit\Purchasing\Actions;

use App\Modules\Auth\Models\User;
use App\Modules\Catalog\Models\Product;
use App\Modules\Purchasing\Actions\ReceivePurchaseOrder;
use App\Modules\Purchasing\Models\PurchaseOrder;
use App\Modules\Purchasing\Models\PurchaseOrderItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class ReceivePurchaseOrderTest extends TestCase
{
    use RefreshDatabase;

    protected ReceivePurchaseOrder $action;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->action = new ReceivePurchaseOrder();
        $this->user = User::factory()->create();
    }

    public function test_receive_updates_status_and_stock(): void
    {
        $product = Product::factory()->create([
            'stock_qty' => 10,
            'cost_price' => 50.00,
        ]);

        // Force set initial stock (factory might not set it if guarded)
        $product->forceFill(['stock_qty' => 10])->save();

        $po = PurchaseOrder::factory()->ordered()->create();

        PurchaseOrderItem::factory()->create([
            'purchase_order_id' => $po->id,
            'product_id' => $product->id,
            'quantity' => 20,
            'unit_cost' => 60.00, // New higher cost
        ]);

        $receivedPo = $this->action->execute($po, $this->user->id);

        $this->assertEquals(PurchaseOrder::STATUS_RECEIVED, $receivedPo->status);
        $this->assertNotNull($receivedPo->received_at);
        $this->assertEquals($this->user->id, $receivedPo->received_by);

        // Check Product Stock Update
        $product->refresh();
        $this->assertEquals(30, $product->stock_qty); // 10 original + 20 received
        $this->assertEquals(60.00, $product->cost_price); // Updated to last purchase price
    }

    public function test_cannot_receive_already_received_po(): void
    {
        $po = PurchaseOrder::factory()->received()->create();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Purchase Order is already received.");

        $this->action->execute($po, $this->user->id);
    }

    public function test_cannot_receive_cancelled_po(): void
    {
        $po = PurchaseOrder::factory()->cancelled()->create();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Cannot receive a cancelled Purchase Order.");

        $this->action->execute($po, $this->user->id);
    }
}
