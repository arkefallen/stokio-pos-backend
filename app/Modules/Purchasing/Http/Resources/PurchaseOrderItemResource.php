<?php

namespace App\Modules\Purchasing\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseOrderItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'product_name' => $this->product->name,
            'product_sku' => $this->product->sku,
            'quantity' => $this->quantity,
            'unit_cost' => (float) $this->unit_cost,
            'subtotal' => (float) $this->subtotal,
        ];
    }
}
