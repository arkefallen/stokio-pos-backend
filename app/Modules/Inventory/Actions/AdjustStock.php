<?php

namespace App\Modules\Inventory\Actions;

use App\Common\Exceptions\StockInsufficiencyException;
use App\Modules\Catalog\Models\Product;
use App\Modules\Inventory\Models\StockMovement;
use Illuminate\Database\Eloquent\Model;

class AdjustStock
{
    /**
     * Adjust stock for a product and log the movement.
     * This action handles locking and validation internally or expects the caller to handle DB transaction.
     *
     * @param Product $product (Ideally pass ID, but model is fine if locked outside)
     * @param int $quantityChange Positive to add, Negative to deduct
     * @param string $type StockMovement type constant
     * @param Model|null $reference Reference model (Sale, PO, etc)
     * @param int $userId Who did this
     * @return int New stock quantity
     * @throws StockInsufficiencyException
     */
    public function execute(
        Product $product,
        int $quantityChange,
        string $type,
        ?Model $reference,
        int $userId
    ): int {
        // Validation for deduction
        if ($quantityChange < 0 && $product->stock_qty + $quantityChange < 0) {
            throw new StockInsufficiencyException(
                $product->id,
                abs($quantityChange),
                $product->stock_qty,
                "Insufficient stock for product '{$product->name}'. Available: {$product->stock_qty}, Deducting: " . abs($quantityChange)
            );
        }

        $stockBefore = $product->stock_qty;

        // Update Product Stock
        $product->stock_qty += $quantityChange;
        $product->save(); // Save immediately

        $stockAfter = $product->stock_qty;

        // Log Movement
        StockMovement::create([
            'product_id' => $product->id,
            'type' => $type,
            'reference_type' => $reference ? get_class($reference) : null,
            'reference_id' => $reference ? $reference->id : null,
            'quantity' => $quantityChange,
            'stock_before' => $stockBefore,
            'stock_after' => $stockAfter,
            'user_id' => $userId,
        ]);

        return $stockAfter;
    }
}
