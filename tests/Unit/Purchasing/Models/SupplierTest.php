<?php

namespace Tests\Unit\Purchasing\Models;

use App\Modules\Purchasing\Models\PurchaseOrder;
use App\Modules\Purchasing\Models\Supplier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupplierTest extends TestCase
{
    use RefreshDatabase;

    public function test_supplier_has_many_purchase_orders(): void
    {
        $supplier = Supplier::factory()->create();
        $purchaseOrder = PurchaseOrder::factory()->create(['supplier_id' => $supplier->id]);

        $this->assertTrue($supplier->purchaseOrders->contains($purchaseOrder));
        $this->assertEquals(1, $supplier->purchaseOrders->count());
    }

    public function test_is_active_cast(): void
    {
        $supplier = Supplier::factory()->create(['is_active' => 1]);
        $this->assertTrue($supplier->is_active);
        $this->assertIsBool($supplier->is_active);
    }

    public function test_soft_deletes(): void
    {
        $supplier = Supplier::factory()->create();
        $supplier->delete();

        $this->assertSoftDeleted('suppliers', ['id' => $supplier->id]);
    }
}
