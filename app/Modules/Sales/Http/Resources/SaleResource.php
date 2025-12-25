<?php

namespace App\Modules\Sales\Http\Resources;

use App\Modules\Auth\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SaleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'sale_number' => $this->sale_number,
            'status' => $this->status,
            'payment_status' => $this->payment_status,
            'payment_method' => $this->payment_method,
            'subtotal' => (float) $this->subtotal,
            'tax_amount' => (float) $this->tax_amount,
            'discount_amount' => (float) $this->discount_amount,
            'total_amount' => (float) $this->total_amount,
            'cash_given' => $this->cash_given ? (float) $this->cash_given : null,
            'change_return' => $this->change_return ? (float) $this->change_return : null,
            'notes' => $this->notes,
            'created_by' => new UserResource($this->whenLoaded('creator')),
            'items' => SaleItemResource::collection($this->whenLoaded('items')),
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
