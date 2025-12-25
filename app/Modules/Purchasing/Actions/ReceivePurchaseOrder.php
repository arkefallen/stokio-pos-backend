<?php

namespace App\Modules\Purchasing\Actions;

use App\Common\Exceptions\StockInsufficiencyException;
use App\Modules\Catalog\Models\Product;
use App\Modules\Purchasing\Models\PurchaseOrder;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ReceivePurchaseOrder
{
    /**
     * Mark Purchase Order as received and update stock
     *
     * @param PurchaseOrder $po
     * @param int $userId Receiver ID
     * @return PurchaseOrder
     * @throws InvalidArgumentException
     */
    public function execute(PurchaseOrder $po, int $userId): PurchaseOrder
    {
        if ($po->status === PurchaseOrder::STATUS_RECEIVED) {
            throw new InvalidArgumentException("Purchase Order is already received.");
        }

        if ($po->status === PurchaseOrder::STATUS_CANCELLED) {
            throw new InvalidArgumentException("Cannot receive a cancelled Purchase Order.");
        }

        return DB::transaction(function () use ($po, $userId) {
            // Update PO status
            $po->update([
                'status' => PurchaseOrder::STATUS_RECEIVED,
                'received_at' => now(),
                'received_by' => $userId,
            ]);

            // Update Stock
            foreach ($po->items as $item) {
                $product = Product::lockForUpdate()->find($item->product_id);
                if ($product) {
                    // Update Cost Price (Last Purchase Price)
                    $product->cost_price = $item->unit_cost;
                    // Note: Product is saved inside AdjustStock action

                    // Increment Stock & Log Movement
                    (new \App\Modules\Inventory\Actions\AdjustStock)->execute(
                        $product,
                        $item->quantity,
                        \App\Modules\Inventory\Models\StockMovement::TYPE_PURCHASE,
                        $po,
                        $userId
                    );
                }
            }

            return $po;
        });
    }
}
