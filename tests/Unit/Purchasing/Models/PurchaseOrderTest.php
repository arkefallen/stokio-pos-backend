<?php

namespace Tests\Unit\Purchasing\Models;

use App\Modules\Auth\Models\User;
use App\Modules\Purchasing\Models\PurchaseOrder;
use App\Modules\Purchasing\Models\PurchaseOrderItem;
use App\Modules\Purchasing\Models\Supplier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_belongs_to_supplier(): void
    {
        $supplier = Supplier::factory()->create();
        $po = PurchaseOrder::factory()->create(['supplier_id' => $supplier->id]);

        $this->assertInstanceOf(Supplier::class, $po->supplier);
        $this->assertEquals($supplier->id, $po->supplier->id);
    }

    public function test_belongs_to_creator(): void
    {
        $user = User::factory()->create();
        $po = PurchaseOrder::factory()->create(['created_by' => $user->id]);

        $this->assertInstanceOf(User::class, $po->creator);
        $this->assertEquals($user->id, $po->creator->id);
    }

    public function test_belongs_to_receiver(): void
    {
        $user = User::factory()->create();
        $po = PurchaseOrder::factory()->received()->create(['received_by' => $user->id]);

        $this->assertInstanceOf(User::class, $po->receiver);
        $this->assertEquals($user->id, $po->receiver->id);
    }

    public function test_has_many_items(): void
    {
        $po = PurchaseOrder::factory()->create();
        $item = PurchaseOrderItem::factory()->create(['purchase_order_id' => $po->id]);

        $this->assertTrue($po->items->contains($item));
    }

    public function test_total_attribute_calculation(): void
    {
        $po = PurchaseOrder::factory()->create();

        PurchaseOrderItem::factory()->create([
            'purchase_order_id' => $po->id,
            'quantity' => 2,
            'unit_cost' => 10.00,
            'subtotal' => 20.00,
        ]);

        PurchaseOrderItem::factory()->create([
            'purchase_order_id' => $po->id,
            'quantity' => 1,
            'unit_cost' => 50.00,
            'subtotal' => 50.00,
        ]);

        // 20 + 50 = 70
        $this->assertEquals(70.00, $po->total);
    }
}
