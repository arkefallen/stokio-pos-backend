<?php

namespace App\Modules\Inventory\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreStockAdjustmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Admin/Manager only ideally
    }

    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'in:damaged,lost,correction,other'],
            'notes' => ['nullable', 'string', 'max:500'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity_change' => ['required', 'integer', 'not_in:0'], // Must change something
        ];
    }
}
