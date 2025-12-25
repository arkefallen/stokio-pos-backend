<?php

namespace Tests\Feature\Catalog;

use App\Modules\Auth\Models\User;
use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use RefreshDatabase;

    protected string $endpoint = '/api/products';

    protected function setUp(): void
    {
        parent::setUp();
        // Fake the minio disk for any file operations
        Storage::fake('minio');
    }

    /**
     * ===================================
     * INDEX (LIST) SCENARIOS
     * ===================================
     */

    public function test_authenticated_user_can_list_products(): void
    {
        Product::factory()->count(15)->create();
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson($this->endpoint);

        $response->assertStatus(200)
            ->assertJsonCount(15, 'data');
    }

    public function test_list_pagination(): void
    {
        Product::factory()->count(25)->create();
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson($this->endpoint . '?per_page=10');

        $response->assertStatus(200)
            ->assertJsonCount(10, 'data')
            ->assertJsonPath('meta.total', 25)
            ->assertJsonPath('meta.per_page', 10);
    }

    public function test_list_filter_by_category(): void
    {
        $cat1 = Category::factory()->create();
        $cat2 = Category::factory()->create();

        Product::factory()->count(3)->create(['category_id' => $cat1->id]);
        Product::factory()->count(2)->create(['category_id' => $cat2->id]);

        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson($this->endpoint . '?category_id=' . $cat1->id);

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    public function test_list_filter_search_name(): void
    {
        Product::factory()->create(['name' => 'Apple iPhone']);
        Product::factory()->create(['name' => 'Samsung Galaxy']);

        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson($this->endpoint . '?search=iPhone');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Apple iPhone');
    }

    public function test_list_filter_stock_status(): void
    {
        // One in stock
        $p1 = Product::factory()->create(['stock_qty' => 10]);
        // Force set stock 10
        $p1->forceFill(['stock_qty' => 10])->save();

        // One out of stock
        $p2 = Product::factory()->create(['stock_qty' => 0]);
        $p2->forceFill(['stock_qty' => 0])->save();

        $user = User::factory()->create();
        Sanctum::actingAs($user);

        // Filter in_stock=true
        $this->getJson($this->endpoint . '?in_stock=1')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $p1->id);

        // Filter in_stock=false
        $this->getJson($this->endpoint . '?in_stock=0')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $p2->id);
    }

    /**
     * ===================================
     * STORE (CREATE) SCENARIOS
     * ===================================
     */

    public function test_admin_can_create_product_with_image(): void
    {
        $admin = User::factory()->admin()->create();
        $category = Category::factory()->create();
        Sanctum::actingAs($admin);

        $image = UploadedFile::fake()->image('product.jpg');

        $response = $this->postJson($this->endpoint, [
            'category_id' => $category->id,
            'name' => 'New Product',
            'sku' => 'PROD-001',
            'price' => 99.99,
            'image' => $image,
        ]);

        $response->assertStatus(201);

        $product = Product::where('sku', 'PROD-001')->first();
        $this->assertNotNull($product);
        $this->assertNotNull($product->image_path);

        // Verify file stored in Minio
        Storage::disk('minio')->assertExists($product->image_path);
    }

    public function test_create_validation_failures(): void
    {
        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);

        $response = $this->postJson($this->endpoint, []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['category_id', 'name', 'sku', 'price']);
    }

    public function test_create_sku_must_be_unique(): void
    {
        Product::factory()->create(['sku' => 'EXISTING-SKU']);
        $category = Category::factory()->create();

        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);

        $response = $this->postJson($this->endpoint, [
            'category_id' => $category->id,
            'name' => 'Another Product',
            'sku' => 'EXISTING-SKU',
            'price' => 50,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['sku']);
    }

    /**
     * ===================================
     * UPDATE SCENARIOS
     * ===================================
     */

    public function test_admin_can_update_product(): void
    {
        $product = Product::factory()->create(['name' => 'Old Name']);
        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);

        $response = $this->postJson("{$this->endpoint}/{$product->id}", [
            'name' => 'Updated Name',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'Updated Name');

        $this->assertEquals('Updated Name', $product->fresh()->name);
    }

    public function test_update_ignores_stock_qty(): void
    {
        $product = Product::factory()->create(['stock_qty' => 10]);
        $product->forceFill(['stock_qty' => 10])->save(); // Ensure guarded set

        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);

        $response = $this->postJson("{$this->endpoint}/{$product->id}", [
            'name' => $product->name,
            'stock_qty' => 100, // Attempt to hack stock
        ]);

        $response->assertStatus(200);
        $this->assertEquals(10, $product->fresh()->stock_qty);
    }

    /**
     * ===================================
     * DELETE SCENARIOS
     * ===================================
     */

    public function test_admin_can_soft_delete_product(): void
    {
        $product = Product::factory()->create();
        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);

        $this->deleteJson("{$this->endpoint}/{$product->id}")
            ->assertStatus(200);

        $this->assertSoftDeleted('products', ['id' => $product->id]);
    }

    public function test_cashier_cannot_delete_product(): void
    {
        $product = Product::factory()->create();
        $cashier = User::factory()->cashier()->create();
        Sanctum::actingAs($cashier);

        $this->deleteJson("{$this->endpoint}/{$product->id}")
            ->assertStatus(403);
    }
}
