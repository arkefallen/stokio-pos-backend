<?php

namespace Tests\Unit\Catalog\Models;

use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_category_has_many_products(): void
    {
        $category = Category::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id]);

        $this->assertTrue($category->products->contains($product));
        $this->assertEquals(1, $category->products->count());
    }

    public function test_scope_active(): void
    {
        Category::factory()->create(['is_active' => true]);
        Category::factory()->inactive()->create();

        $this->assertEquals(1, Category::active()->count());
        $this->assertTrue(Category::active()->first()->is_active);
    }

    public function test_casts_active_to_boolean(): void
    {
        $category = Category::factory()->create(['is_active' => 1]);
        $this->assertTrue($category->is_active);
        $this->assertIsBool($category->is_active);
    }
}
