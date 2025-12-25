<?php

namespace Tests\Unit\Catalog\Actions;

use App\Modules\Catalog\Actions\UpdateProduct;
use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Services\MinioStorageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class UpdateProductTest extends TestCase
{
    use RefreshDatabase;

    protected MockInterface $storageMock;
    protected UpdateProduct $action;

    protected function setUp(): void
    {
        parent::setUp();

        $this->storageMock = Mockery::mock(MinioStorageService::class);
        $this->action = new UpdateProduct($this->storageMock);
    }

    public function test_update_product_success(): void
    {
        $product = Product::factory()->create([
            'name' => 'Old Name',
            'price' => 50.00,
        ]);

        $data = [
            'name' => 'New Name',
            'price' => 75.00,
        ];

        $updatedProduct = $this->action->execute($product, $data);

        $this->assertEquals('New Name', $updatedProduct->name);
        $this->assertEquals(75.00, $updatedProduct->price);
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'New Name',
            'price' => 75.00,
        ]);
    }

    public function test_update_product_handles_image_replacement(): void
    {
        $product = Product::factory()->create([
            'image_path' => 'products/old-image.jpg',
        ]);

        $image = UploadedFile::fake()->image('new-product.jpg');
        $expectedPath = 'products/new-image.jpg';

        // Expect upload call with old path for deletion/replacement handling in service
        $this->storageMock->shouldReceive('uploadProductImage')
            ->once()
            ->with($image, 'products/old-image.jpg')
            ->andReturn($expectedPath);

        $data = [
            'name' => 'Updated Product',
        ];

        $updatedProduct = $this->action->execute($product, $data, $image);

        $this->assertEquals($expectedPath, $updatedProduct->image_path);
    }

    public function test_update_product_ignores_stock_qty(): void
    {
        $product = Product::factory()->create([
            'stock_qty' => 10,
        ]);
        // Force set stock to 10 if factory didn't (factory creates with 0 if guarded)
        $product->forceFill(['stock_qty' => 10])->save();

        $data = [
            'name' => 'Product Name',
            'stock_qty' => 100, // Should be ignored
        ];

        $updatedProduct = $this->action->execute($product, $data);

        // Stock should remain 10
        $this->assertEquals(10, $updatedProduct->stock_qty);
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'stock_qty' => 10,
        ]);
    }

    public function test_update_product_can_change_category(): void
    {
        $oldCategory = Category::factory()->create();
        $newCategory = Category::factory()->create();

        $product = Product::factory()->create(['category_id' => $oldCategory->id]);

        $data = ['category_id' => $newCategory->id];

        $updatedProduct = $this->action->execute($product, $data);

        $this->assertEquals($newCategory->id, $updatedProduct->category_id);
    }
}
