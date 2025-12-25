<?php

namespace Tests\Unit\Catalog\Actions;

use App\Modules\Auth\Models\User;
use App\Modules\Catalog\Actions\CreateProduct;
use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Services\MinioStorageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class CreateProductTest extends TestCase
{
    use RefreshDatabase;

    protected MockInterface $storageMock;
    protected CreateProduct $action;

    protected function setUp(): void
    {
        parent::setUp();

        $this->storageMock = Mockery::mock(MinioStorageService::class);
        $this->action = new CreateProduct($this->storageMock);
    }

    public function test_create_product_success_with_required_fields(): void
    {
        $category = Category::factory()->create();

        $data = [
            'category_id' => $category->id,
            'name' => 'Test Product',
            'sku' => 'TEST-001',
            'price' => 100.00,
        ];

        $product = $this->action->execute($data);

        $this->assertInstanceOf(Product::class, $product);
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Test Product',
            'sku' => 'TEST-001',
            'price' => 100.00,
            'is_active' => true, // default
            'min_stock' => 0, // default
        ]);
    }

    public function test_create_product_with_image_delegates_to_storage_service(): void
    {
        $category = Category::factory()->create();
        $image = UploadedFile::fake()->image('product.jpg');
        $expectedPath = 'products/mock-path.jpg';

        // Expect upload call
        $this->storageMock->shouldReceive('uploadProductImage')
            ->once()
            ->with($image)
            ->andReturn($expectedPath);

        $data = [
            'category_id' => $category->id,
            'name' => 'Test Product Image',
            'sku' => 'IMG-001',
            'price' => 50.00,
        ];

        $product = $this->action->execute($data, $image);

        $this->assertEquals($expectedPath, $product->image_path);
        $this->assertDatabaseHas('products', [
            'sku' => 'IMG-001',
            'image_path' => $expectedPath,
        ]);
    }

    public function test_create_product_sets_creator(): void
    {
        $category = Category::factory()->create();
        $user = User::factory()->create();

        $data = [
            'category_id' => $category->id,
            'name' => 'Tracked Product',
            'sku' => 'TRK-001',
            'price' => 99.99,
        ];

        $product = $this->action->execute($data, null, $user->id);

        $this->assertEquals($user->id, $product->created_by);
    }

    public function test_create_product_handles_optional_fields(): void
    {
        $category = Category::factory()->create();

        $data = [
            'category_id' => $category->id,
            'name' => 'Full Product',
            'sku' => 'FULL-001',
            'description' => 'A detailed description',
            'price' => 199.99,
            'cost_price' => 150.00,
            'min_stock' => 10,
            'is_active' => false,
        ];

        $product = $this->action->execute($data);

        $this->assertDatabaseHas('products', [
            'sku' => 'FULL-001',
            'description' => 'A detailed description',
            'cost_price' => 150.00,
            'min_stock' => 10,
            'is_active' => false,
        ]);
    }
}
