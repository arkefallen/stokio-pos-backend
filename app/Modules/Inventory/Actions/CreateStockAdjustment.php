<?php

namespace App\Modules\Inventory\Actions;

use App\Common\Exceptions\StockInsufficiencyException;
use App\Modules\Catalog\Models\Product;
use App\Modules\Inventory\Models\StockAdjustment;
use App\Modules\Inventory\Models\StockMovement;
use Illuminate\Support\Facades\DB;

class CreateStockAdjustment
{
    /**
     * Create a stock adjustment (Opname)
     *
     * @param array $data
     * @param int $userId
     * @return StockAdjustment
     */
    public function execute(array $data, int $userId): StockAdjustment
    {
        return DB::transaction(function () use ($data, $userId) {
            // 1. Create Header
            $adjustment = StockAdjustment::create([
                'reason' => $data['reason'],
                'notes' => $data['notes'] ?? null,
                'created_by' => $userId,
            ]);

            // 2. Process Items
            foreach ($data['items'] as $item) {
                $product = Product::lockForUpdate()->find($item['product_id']);

                if (!$product) {
                    throw new \Exception("Product ID {$item['product_id']} not found.");
                }

                // Call AdjustStock Action
                (new AdjustStock)->execute(
                    $product,
                    $item['quantity_change'],
                    StockMovement::TYPE_ADJUSTMENT,
                    $adjustment,
                    $userId
                );
            }

            return $adjustment;
        });
    }
}
