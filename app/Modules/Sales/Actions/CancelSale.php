<?php

namespace App\Modules\Sales\Actions;

use App\Modules\Catalog\Models\Product;
use App\Modules\Sales\Models\Sale;
use Illuminate\Support\Facades\DB;

class CancelSale
{
    /**
     * Cancel a sale and restore stock
     *
     * @param Sale $sale
     * @param int $userId
     * @return Sale
     */
    public function execute(Sale $sale, int $userId): Sale
    {
        if ($sale->status === Sale::STATUS_CANCELLED) {
            throw new \Exception("Sale is already cancelled.");
        }

        return DB::transaction(function () use ($sale, $userId) {
            // 1. Restore Stock
            foreach ($sale->items as $item) {
                // Lock product to prevent race conditions
                $product = Product::lockForUpdate()->find($item->product_id);
                if ($product) {
                    $product->stock_qty += $item->quantity;
                    $product->save();
                }
            }

            // 2. Update Status
            $sale->status = Sale::STATUS_CANCELLED;
            $sale->notes .= "\n[Cancelled by User ID: $userId at " . now() . "]";
            $sale->save();

            return $sale;
        });
    }
}
