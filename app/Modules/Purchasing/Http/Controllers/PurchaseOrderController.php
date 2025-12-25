<?php

namespace App\Modules\Purchasing\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Purchasing\Actions\CreatePurchaseOrder;
use App\Modules\Purchasing\Actions\ReceivePurchaseOrder;
use App\Modules\Purchasing\Http\Requests\StorePurchaseOrderRequest;
use App\Modules\Purchasing\Http\Resources\PurchaseOrderResource;
use App\Modules\Purchasing\Models\PurchaseOrder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PurchaseOrderController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = PurchaseOrder::with(['supplier', 'creator']);

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->integer('supplier_id'));
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date('date_to'));
        }

        $pos = $query->orderBy('created_at', 'desc')->paginate($request->integer('per_page', 15));

        return response()->json([
            'data' => PurchaseOrderResource::collection($pos),
            'meta' => [
                'current_page' => $pos->currentPage(),
                'last_page' => $pos->lastPage(),
                'total' => $pos->total(),
            ],
        ]);
    }

    public function show(PurchaseOrder $purchaseOrder): JsonResponse
    {
        $purchaseOrder->load(['supplier', 'items.product', 'creator', 'receiver']);

        return response()->json([
            'data' => new PurchaseOrderResource($purchaseOrder),
        ]);
    }

    public function store(StorePurchaseOrderRequest $request, CreatePurchaseOrder $action): JsonResponse
    {
        $po = $action->execute($request->validated(), $request->user()->id);
        $po->load(['supplier']);

        return response()->json([
            'message' => 'Purchase Order created successfully.',
            'data' => new PurchaseOrderResource($po),
        ], 201);
    }

    public function receive(Request $request, PurchaseOrder $purchaseOrder, ReceivePurchaseOrder $action): JsonResponse
    {
        if (!$request->user()->isAdminOrManager()) {
            abort(403, 'Unauthorized');
        }

        try {
            $po = $action->execute($purchaseOrder, $request->user()->id);

            return response()->json([
                'message' => 'Purchase Order received and stock updated successfully.',
                'data' => new PurchaseOrderResource($po),
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function cancel(Request $request, PurchaseOrder $purchaseOrder): JsonResponse
    {
        if (!$request->user()->isAdminOrManager()) {
            abort(403, 'Unauthorized');
        }

        if ($purchaseOrder->status !== PurchaseOrder::STATUS_PENDING) {
            return response()->json([
                'message' => 'Only pending orders can be cancelled.',
            ], 422);
        }

        $purchaseOrder->update(['status' => PurchaseOrder::STATUS_CANCELLED]);

        return response()->json([
            'message' => 'Purchase Order cancelled successfully.',
            'data' => new PurchaseOrderResource($purchaseOrder),
        ]);
    }
}
