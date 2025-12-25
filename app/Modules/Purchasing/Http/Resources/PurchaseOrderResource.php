<?php

namespace App\Modules\Purchasing\Http\Resources;

use App\Modules\Auth\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseOrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'purchase_number' => $this->purchase_number,
            'supplier' => new SupplierResource($this->whenLoaded('supplier')),
            'status' => $this->status,
            'ordered_at' => $this->created_at->format('Y-m-d'),
            'expected_delivery_date' => $this->expected_delivery_date?->format('Y-m-d'),
            'received_at' => $this->received_at?->toIso8601String(),
            'notes' => $this->notes,
            'total_amount' => (float) $this->total,
            'created_by' => new UserResource($this->whenLoaded('creator')),
            'received_by' => new UserResource($this->whenLoaded('receiver')),
            'items' => PurchaseOrderItemResource::collection($this->whenLoaded('items')),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
