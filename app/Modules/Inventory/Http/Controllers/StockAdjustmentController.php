<?php

namespace App\Modules\Inventory\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Inventory\Actions\CreateStockAdjustment;
use App\Modules\Inventory\Http\Requests\StoreStockAdjustmentRequest;
use Illuminate\Http\JsonResponse;

class StockAdjustmentController extends Controller
{
    public function store(StoreStockAdjustmentRequest $request, CreateStockAdjustment $action): JsonResponse
    {
        $this->ensureValidJson($request);

        try {
            $adjustment = $action->execute($request->validated(), $request->user()->id);

            return response()->json([
                'message' => 'Stock adjustment created successfully.',
                'data' => [
                    'id' => $adjustment->id,
                    'reason' => $adjustment->reason,
                    'created_at' => $adjustment->created_at,
                ],
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
