<?php

namespace Tests\Unit\Catalog\Actions;

use App\Modules\Catalog\Actions\DeleteProduct;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Services\MinioStorageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class DeleteProductTest extends TestCase
{
    use RefreshDatabase;

    protected MockInterface $storageMock;
    protected DeleteProduct $action;

    protected function setUp(): void
    {
        parent::setUp();

        $this->storageMock = Mockery::mock(MinioStorageService::class);
        $this->action = new DeleteProduct($this->storageMock);
    }

    public function test_execute_performs_soft_delete(): void
    {
        $product = Product::factory()->create();

        $result = $this->action->execute($product);

        $this->assertTrue($result);
        $this->assertSoftDeleted('products', ['id' => $product->id]);
    }

    public function test_execute_does_not_call_storage_delete(): void
    {
        $product = Product::factory()->create([
            'image_path' => 'products/image.jpg',
        ]);

        // Should NOT call deleteFile
        $this->storageMock->shouldNotReceive('deleteFile');

        $this->action->execute($product);
    }

    public function test_force_delete_removes_record_permanently(): void
    {
        $product = Product::factory()->create();

        $result = $this->action->forceDelete($product);

        $this->assertTrue($result);
        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }

    public function test_force_delete_removes_image_from_storage(): void
    {
        $product = Product::factory()->create([
            'image_path' => 'products/image.jpg',
        ]);

        // Expect deleteFile call
        $this->storageMock->shouldReceive('deleteFile')
            ->once()
            ->with('products/image.jpg');

        $this->action->forceDelete($product);
    }

    public function test_force_delete_handles_product_without_image(): void
    {
        $product = Product::factory()->create([
            'image_path' => null,
        ]);

        // Should NOT call deleteFile
        $this->storageMock->shouldNotReceive('deleteFile');

        $this->action->forceDelete($product);

        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }
}
