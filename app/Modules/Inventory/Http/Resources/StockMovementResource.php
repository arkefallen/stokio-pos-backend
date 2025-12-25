<?php

namespace App\Modules\Inventory\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockMovementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // Try to get Reference Number
        $refNumber = null;
        if ($this->reference) {
            if (isset($this->reference->sale_number))
                $refNumber = $this->reference->sale_number;
            elseif (isset($this->reference->po_number))
                $refNumber = $this->reference->po_number;
            elseif (isset($this->reference->reason))
                $refNumber = 'ADJ-' . $this->reference->id;
        }

        return [
            'id' => $this->id,
            'date' => $this->created_at->toIso8601String(),
            'product_name' => $this->product->name ?? 'Unknown',
            'type' => $this->type,
            'quantity_change' => (int) $this->quantity,
            'stock_after' => (int) $this->stock_after,
            'reference_number' => $refNumber,
            'user' => $this->user->name ?? 'Unknown',
        ];
    }
}
