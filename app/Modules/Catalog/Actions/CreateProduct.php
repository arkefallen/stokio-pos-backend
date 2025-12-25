<?php

namespace App\Modules\Catalog\Actions;

use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Services\MinioStorageService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class CreateProduct
{
    public function __construct(
        protected MinioStorageService $storage
    ) {
    }

    /**
     * Create a new product
     *
     * @param array $data Product data
     * @param UploadedFile|null $image Optional product image
     * @param int|null $createdBy User ID who creates this product
     * @return Product
     */
    public function execute(array $data, ?UploadedFile $image = null, ?int $createdBy = null): Product
    {
        return DB::transaction(function () use ($data, $image, $createdBy) {
            // Upload image if provided
            $imagePath = null;
            if ($image) {
                $imagePath = $this->storage->uploadProductImage($image);
            }

            // Create product
            $product = Product::create([
                'category_id' => $data['category_id'],
                'name' => $data['name'],
                'sku' => $data['sku'],
                'description' => $data['description'] ?? null,
                'price' => $data['price'],
                'cost_price' => $data['cost_price'] ?? null,
                'min_stock' => $data['min_stock'] ?? 0,
                'image_path' => $imagePath,
                'is_active' => $data['is_active'] ?? true,
                'created_by' => $createdBy,
            ]);

            return $product;
        });
    }
}
