<?php

namespace App\Modules\Catalog\Actions;

use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Services\MinioStorageService;
use Illuminate\Support\Facades\DB;

class DeleteProduct
{
    public function __construct(
        protected MinioStorageService $storage
    ) {
    }

    /**
     * Soft delete a product
     * NOTE: Image is NOT deleted to allow for potential restoration
     *
     * @param Product $product
     * @return bool
     */
    public function execute(Product $product): bool
    {
        return DB::transaction(function () use ($product) {
            // Soft delete only - image preserved for potential restore
            return $product->delete();
        });
    }

    /**
     * Permanently delete a product and its image
     *
     * @param Product $product
     * @return bool
     */
    public function forceDelete(Product $product): bool
    {
        return DB::transaction(function () use ($product) {
            // Delete image from storage
            if ($product->image_path) {
                $this->storage->deleteFile($product->image_path);
            }

            // Force delete
            return $product->forceDelete();
        });
    }
}
