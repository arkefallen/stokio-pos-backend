<?php

namespace App\Modules\Purchasing\Actions;

use App\Modules\Purchasing\Models\PurchaseOrder;
use App\Modules\Purchasing\Models\PurchaseOrderItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreatePurchaseOrder
{
    /**
     * Create a new purchase order
     *
     * @param array $data PO data including items
     * @param int $userId Creator ID
     * @return PurchaseOrder
     */
    public function execute(array $data, int $userId): PurchaseOrder
    {
        return DB::transaction(function () use ($data, $userId) {
            // Generate PO Number: PO-YYYYMMDD-XXXX
            $date = now()->format('Ymd');
            $count = PurchaseOrder::whereDate('created_at', today())->count() + 1;
            $poNumber = 'PO-' . $date . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);

            $po = PurchaseOrder::create([
                'supplier_id' => $data['supplier_id'],
                'purchase_number' => $poNumber,
                'status' => PurchaseOrder::STATUS_PENDING,
                'expected_delivery_date' => $data['expected_delivery_date'] ?? null,
                'notes' => $data['notes'] ?? null,
                'created_by' => $userId,
            ]);

            foreach ($data['items'] as $item) {
                // Calculate subtotal
                $subtotal = $item['quantity'] * $item['unit_cost'];

                PurchaseOrderItem::create([
                    'purchase_order_id' => $po->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_cost' => $item['unit_cost'],
                    'subtotal' => $subtotal,
                ]);
            }

            return $po->load('items');
        });
    }
}
