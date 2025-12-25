<?php

namespace App\Modules\Sales\Actions;

use App\Common\Exceptions\StockInsufficiencyException;
use App\Modules\Catalog\Models\Product;
use App\Modules\Sales\Models\Sale;
use App\Modules\Sales\Models\SaleItem;
use Illuminate\Support\Facades\DB;

class CreateSale
{
    /**
     * Create a new sale (sales transaction)
     *
     * @param array $data Sale data including items
     * @param int $userId Cashier ID
     * @return Sale
     * @throws StockInsufficiencyException
     */
    public function execute(array $data, int $userId): Sale
    {
        return DB::transaction(function () use ($data, $userId) {
            // 1. Prepare Sale Number: TRX-YYYYMMDD-XXXX
            $date = now()->format('Ymd');
            $count = Sale::whereDate('created_at', today())->count() + 1;
            $saleNumber = 'TRX-' . $date . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);

            // 2. Validate Items & Stock (with Locking)
            $itemsData = collect($data['items'])->sortBy('product_id');

            $subtotal = 0;
            $saleItems = [];

            foreach ($itemsData as $item) {
                $product = Product::lockForUpdate()->find($item['product_id']);

                if (!$product) {
                    throw new \Exception("Product ID {$item['product_id']} not found.");
                }

                if ($product->stock_qty < $item['quantity']) {
                    throw new StockInsufficiencyException(
                        "Insufficient stock for product '{$product->name}'. Available: {$product->stock_qty}, Requested: {$item['quantity']}"
                    );
                }

                // Deduct Stock
                $product->stock_qty -= $item['quantity'];
                $product->save();

                // Snapshot price/cost
                $price = $product->price;
                $cost = $product->cost_price;
                $itemSubtotal = $price * $item['quantity'];

                $subtotal += $itemSubtotal;

                $saleItems[] = [
                    'product' => $product,
                    'quantity' => $item['quantity'],
                    'price' => $price,
                    'cost_price' => $cost,
                    'subtotal' => $itemSubtotal,
                ];
            }

            // 3. Calculate Finals
            $discount = $data['discount_amount'] ?? 0;
            $tax = $data['tax_amount'] ?? 0;
            $total = $subtotal + $tax - $discount;

            // Handle Cash Payment
            $cashGiven = $data['cash_given'] ?? null;
            $changeReturn = null;
            $paymentStatus = Sale::PAYMENT_UNPAID;

            if ($data['payment_method'] === Sale::METHOD_CASH && $cashGiven !== null) {
                if ($cashGiven < $total) {
                    throw new \Exception("Cash given ($cashGiven) is less than total amount ($total).");
                }
                $changeReturn = $cashGiven - $total;
                $paymentStatus = Sale::PAYMENT_PAID;
            } elseif ($data['payment_method'] !== Sale::METHOD_CASH) {
                $paymentStatus = Sale::PAYMENT_PAID;
            }

            // 4. Create Sale
            $sale = Sale::create([
                'sale_number' => $saleNumber,
                'status' => Sale::STATUS_COMPLETED,
                'payment_status' => $paymentStatus,
                'payment_method' => $data['payment_method'] ?? null,
                'subtotal' => $subtotal,
                'tax_amount' => $tax,
                'discount_amount' => $discount,
                'total_amount' => $total,
                'cash_given' => $cashGiven,
                'change_return' => $changeReturn,
                'notes' => $data['notes'] ?? null,
                'created_by' => $userId,
            ]);

            // 5. Create Sale Items
            foreach ($saleItems as $item) {
                SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $item['product']->id,
                    'product_name' => $item['product']->name,
                    'product_sku' => $item['product']->sku,
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                    'cost_price' => $item['cost_price'] ?? 0,
                    'subtotal' => $item['subtotal'],
                ]);
            }

            return $sale->load('items');
        });
    }
}
