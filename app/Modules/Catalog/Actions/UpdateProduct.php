<?php

namespace App\Modules\Catalog\Actions;

use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Services\MinioStorageService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class UpdateProduct
{
    public function __construct(
        protected MinioStorageService $storage
    ) {
    }

    /**
     * Update an existing product
     * NOTE: stock_qty cannot be updated through this action
     *
     * @param Product $product
     * @param array $data
     * @param UploadedFile|null $image
     * @return Product
     */
    public function execute(Product $product, array $data, ?UploadedFile $image = null): Product
    {
        return DB::transaction(function () use ($product, $data, $image) {
            // Handle image replacement
            if ($image) {
                $data['image_path'] = $this->storage->uploadProductImage($image, $product->image_path);
            }

            // Remove stock_qty from data if present (cannot be updated here)
            unset($data['stock_qty']);

            // Update product
            $product->update($data);

            return $product->fresh();
        });
    }
}
