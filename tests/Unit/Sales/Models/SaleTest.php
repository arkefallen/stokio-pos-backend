<?php

namespace Tests\Unit\Sales\Models;

use App\Modules\Auth\Models\User;
use App\Modules\Sales\Models\Sale;
use App\Modules\Sales\Models\SaleItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SaleTest extends TestCase
{
    use RefreshDatabase;

    public function test_relationships(): void
    {
        $sale = Sale::factory()->create();
        $item = SaleItem::factory()->create(['sale_id' => $sale->id]);

        $this->assertTrue($sale->items->contains($item));
        $this->assertInstanceOf(User::class, $sale->creator);
    }

    public function test_casts(): void
    {
        $sale = Sale::factory()->create([
            'subtotal' => 100.50,
            'tax_amount' => 10.05,
            'total_amount' => 110.55,
        ]);

        $this->assertEquals(100.50, $sale->subtotal);
        $this->assertEquals(10.05, $sale->tax_amount);
        $this->assertEquals(110.55, $sale->total_amount);
    }
}
