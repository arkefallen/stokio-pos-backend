<?php

namespace App\Modules\Reports\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Catalog\Models\Product;
use App\Modules\Sales\Models\Sale;
use App\Modules\Sales\Models\SaleItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Get summary stats for dashboard widgets
     */
    public function summary(Request $request): JsonResponse
    {
        // 1. Sales Today
        $today = now()->startOfDay();

        $totalSalesToday = Sale::where('status', Sale::STATUS_COMPLETED)
            ->where('created_at', '>=', $today)
            ->sum('total_amount');

        $transactionCountToday = Sale::where('status', Sale::STATUS_COMPLETED)
            ->where('created_at', '>=', $today)
            ->count();

        // 2. Gross Profit Today (Total Sales - Total HPP)
        // We need to sum from Sale Items because Cost Price is stored there
        $totalCostToday = SaleItem::whereHas('sale', function ($q) use ($today) {
            $q->where('created_at', '>=', $today)
                ->where('status', Sale::STATUS_COMPLETED);
        })
            ->select(DB::raw('SUM(cost_price * quantity) as total_cost'))
            ->value('total_cost');

        $grossProfitToday = $totalSalesToday - ($totalCostToday ?? 0);

        // 3. Low Stock Count (Items with stock < 10)
        $lowStockCount = Product::where('stock_qty', '<=', 10)->count();

        return response()->json([
            'data' => [
                'sales_today' => (float) $totalSalesToday,
                'transactions_today' => $transactionCountToday,
                'gross_profit_today' => (float) $grossProfitToday,
                'low_stock_count' => $lowStockCount,
            ]
        ]);
    }

    /**
     * Get Top Selling Products (by Quantity)
     */
    public function topProducts(Request $request): JsonResponse
    {
        $limit = $request->integer('limit', 5);
        $dateFrom = $request->date('date_from') ?? now()->subDays(30);

        $topProducts = SaleItem::select(
            'product_name',
            DB::raw('SUM(quantity) as total_qty'),
            DB::raw('SUM(subtotal) as total_revenue')
        )
            ->whereHas('sale', function ($q) use ($dateFrom) {
                $q->where('status', Sale::STATUS_COMPLETED)
                    ->where('created_at', '>=', $dateFrom);
            })
            ->groupBy('product_name')
            ->orderByDesc('total_qty')
            ->limit($limit)
            ->get();

        return response()->json([
            'data' => $topProducts
        ]);
    }

    /**
     * Get Daily Sales Trends (for Chart)
     */
    public function salesChart(Request $request): JsonResponse
    {
        $days = $request->integer('days', 7); // Default last 7 days
        $startDate = now()->subDays($days - 1)->startOfDay();

        $sales = Sale::select(
            DB::raw("DATE_TRUNC('day', created_at) as date"),
            DB::raw('SUM(total_amount) as total_revenue'),
            DB::raw('COUNT(*) as total_transactions')
        )
            ->where('status', Sale::STATUS_COMPLETED)
            ->where('created_at', '>=', $startDate)
            ->groupBy(DB::raw("DATE_TRUNC('day', created_at)"))
            ->orderBy('date', 'asc')
            ->get();

        // Format data to ensure all days are represented (even if 0 sales) can be done in FE or detailed loop here.
        // For API simplicity, we return existing data points.

        $formatted = $sales->map(function ($item) {
            return [
                'date' => \Carbon\Carbon::parse($item->date)->format('Y-m-d'),
                'revenue' => (float) $item->total_revenue,
                'transactions' => (int) $item->total_transactions,
            ];
        });

        return response()->json([
            'data' => $formatted
        ]);
    }
}
