<?php

namespace App\Modules\Sales\Http\Controllers;

use App\Common\Exceptions\StockInsufficiencyException;
use App\Http\Controllers\Controller;
use App\Modules\Sales\Actions\CreateSale;
use App\Modules\Sales\Http\Requests\StoreSaleRequest;
use App\Modules\Sales\Http\Resources\SaleResource;
use App\Modules\Sales\Models\Sale;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SaleController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Sale::with(['creator']);

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('sale_number')) {
            $query->where('sale_number', 'like', '%' . $request->string('sale_number') . '%');
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date('date_to'));
        }

        $sales = $query->orderBy('created_at', 'desc')->paginate($request->integer('per_page', 15));

        return response()->json([
            'data' => SaleResource::collection($sales),
            'meta' => [
                'current_page' => $sales->currentPage(),
                'last_page' => $sales->lastPage(),
                'total' => $sales->total(),
            ],
        ]);
    }

    public function show(Sale $sale): JsonResponse
    {
        $sale->load(['items', 'creator']);

        return response()->json([
            'data' => new SaleResource($sale),
        ]);
    }

    public function store(StoreSaleRequest $request, CreateSale $action): JsonResponse
    {
        $this->ensureValidJson($request);

        try {
            $sale = $action->execute($request->validated(), $request->user()->id);

            return response()->json([
                'message' => 'Sale created successfully.',
                'data' => new SaleResource($sale),
            ], 201);

        } catch (StockInsufficiencyException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to create sale: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function cancel(Sale $sale, \App\Modules\Sales\Actions\CancelSale $action, Request $request): JsonResponse
    {
        try {
            $action->execute($sale, $request->user()->id);

            return response()->json([
                'message' => 'Sale cancelled and stock restored successfully.',
                'data' => new SaleResource($sale->fresh()),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
