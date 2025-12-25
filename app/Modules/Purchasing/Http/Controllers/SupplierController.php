<?php

namespace App\Modules\Purchasing\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Purchasing\Http\Requests\StoreSupplierRequest;
use App\Modules\Purchasing\Http\Requests\UpdateSupplierRequest;
use App\Modules\Purchasing\Http\Resources\SupplierResource;
use App\Modules\Purchasing\Models\Supplier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Supplier::query();

        if ($request->has('active')) {
            $query->where('is_active', $request->boolean('active'));
        }

        if ($request->filled('search')) {
            $search = $request->string('search');
            $query->where('name', 'ilike', "%{$search}%")
                ->orWhere('contact_person', 'ilike', "%{$search}%");
        }

        $suppliers = $query->orderBy('name')->paginate($request->integer('per_page', 15));

        return response()->json([
            'data' => SupplierResource::collection($suppliers),
            'meta' => [
                'current_page' => $suppliers->currentPage(),
                'last_page' => $suppliers->lastPage(),
                'total' => $suppliers->total(),
            ],
        ]);
    }

    public function show(Supplier $supplier): JsonResponse
    {
        return response()->json([
            'data' => new SupplierResource($supplier),
        ]);
    }

    public function store(StoreSupplierRequest $request): JsonResponse
    {
        $supplier = Supplier::create($request->validated());

        return response()->json([
            'message' => 'Supplier created successfully.',
            'data' => new SupplierResource($supplier),
        ], 201);
    }

    public function update(UpdateSupplierRequest $request, Supplier $supplier): JsonResponse
    {
        $this->ensureValidJson($request);

        $data = $request->validated();
        $this->ensureDataNotEmpty($data);

        $supplier->update($data);

        return response()->json([
            'message' => 'Supplier updated successfully.',
            'data' => new SupplierResource($supplier->fresh()),
        ]);
    }

    public function destroy(Request $request, Supplier $supplier): JsonResponse
    {
        if (!$request->user()->isAdminOrManager()) {
            abort(403, 'Unauthorized');
        }

        if ($supplier->purchaseOrders()->exists()) {
            return response()->json([
                'message' => 'Cannot delete supplier with existing purchase orders.',
            ], 422);
        }

        $supplier->delete();

        return response()->json([
            'message' => 'Supplier deleted successfully.',
        ]);
    }
}
