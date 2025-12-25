<?php

namespace App\Modules\Catalog\Http\Resources;

use App\Modules\Catalog\Services\MinioStorageService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'category_id' => $this->category_id,
            'category' => new CategoryResource($this->whenLoaded('category')),
            'name' => $this->name,
            'sku' => $this->sku,
            'description' => $this->description,
            'price' => (float) $this->price,
            'cost_price' => $this->cost_price ? (float) $this->cost_price : null,
            'stock_qty' => $this->stock_qty,
            'min_stock' => $this->min_stock,
            'is_low_stock' => $this->isLowStock(),
            'is_in_stock' => $this->isInStock(),
            'image_url' => $this->getImageUrl(),
            'is_active' => $this->is_active,
            'created_by' => $this->created_by,
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }

    /**
     * Generate signed URL for product image
     */
    protected function getImageUrl(): ?string
    {
        if (!$this->image_path) {
            return null;
        }

        $storage = app(MinioStorageService::class);
        return $storage->getSignedUrl($this->image_path, 60);
    }
}
