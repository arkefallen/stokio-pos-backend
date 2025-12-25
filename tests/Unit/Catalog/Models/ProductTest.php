<?php

namespace Tests\Unit\Catalog\Models;

use App\Modules\Auth\Models\User;
use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use RefreshDatabase;

    /**
     * ===================================
     * RELATIONSHIPS
     * ===================================
     */

    public function test_product_belongs_to_category(): void
    {
        $category = Category::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id]);

        $this->assertInstanceOf(Category::class, $product->category);
        $this->assertEquals($category->id, $product->category->id);
    }

    public function test_product_belongs_to_creator(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['created_by' => $user->id]);

        $this->assertInstanceOf(User::class, $product->creator);
        $this->assertEquals($user->id, $product->creator->id);
    }

    /**
     * ===================================
     * SCOPES
     * ===================================
     */

    public function test_scope_active(): void
    {
        Product::factory()->create(['is_active' => true]);
        Product::factory()->inactive()->create();

        $this->assertEquals(1, Product::active()->count());
        $this->assertTrue(Product::active()->first()->is_active);
    }

    public function test_scope_low_stock(): void
    {
        // Low stock: stock <= min_stock
        $lowStock = Product::factory()->create(['stock_qty' => 5, 'min_stock' => 10]);
        // Normal stock: stock > min_stock
        $normalStock = Product::factory()->create(['stock_qty' => 15, 'min_stock' => 10]);

        // We need to force update stock_qty because it's guarded and factory attributes might be ignored during create
        $lowStock->forceFill(['stock_qty' => 5])->save();
        $normalStock->forceFill(['stock_qty' => 15])->save();

        $this->assertEquals(1, Product::lowStock()->count());
        $this->assertEquals($lowStock->id, Product::lowStock()->first()->id);
    }

    public function test_scope_in_stock(): void
    {
        $inStock = Product::factory()->create(['stock_qty' => 5]);
        $outOfStock = Product::factory()->create(['stock_qty' => 0]);

        $inStock->forceFill(['stock_qty' => 5])->save();
        $outOfStock->forceFill(['stock_qty' => 0])->save();

        $this->assertEquals(1, Product::inStock()->count());
        $this->assertEquals($inStock->id, Product::inStock()->first()->id);
    }

    public function test_scope_out_of_stock(): void
    {
        $inStock = Product::factory()->create(['stock_qty' => 5]);
        $outOfStock = Product::factory()->create(['stock_qty' => 0]);
        $negativeStock = Product::factory()->create(['stock_qty' => -1]); // Should theoretically not create negative, but testing logic

        $inStock->forceFill(['stock_qty' => 5])->save();
        $outOfStock->forceFill(['stock_qty' => 0])->save();
        $negativeStock->forceFill(['stock_qty' => -1])->save();

        $this->assertEquals(2, Product::outOfStock()->count());
    }

    /**
     * ===================================
     * METHODS
     * ===================================
     */

    public function test_is_low_stock_check(): void
    {
        $product = Product::factory()->create(['stock_qty' => 5, 'min_stock' => 10]);
        $product->forceFill(['stock_qty' => 5])->save();

        $this->assertTrue($product->isLowStock());

        $product->forceFill(['stock_qty' => 11])->save();
        $this->assertFalse($product->fresh()->isLowStock());
    }

    public function test_is_in_stock_check(): void
    {
        $product = Product::factory()->create(['stock_qty' => 1]);
        $product->forceFill(['stock_qty' => 1])->save();

        $this->assertTrue($product->isInStock());

        $product->forceFill(['stock_qty' => 0])->save();
        $this->assertFalse($product->fresh()->isInStock());
    }
}
