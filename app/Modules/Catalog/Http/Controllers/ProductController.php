<?php

namespace App\Modules\Catalog\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Catalog\Actions\CreateProduct;
use App\Modules\Catalog\Actions\DeleteProduct;
use App\Modules\Catalog\Actions\UpdateProduct;
use App\Modules\Catalog\Http\Requests\StoreProductRequest;
use App\Modules\Catalog\Http\Requests\UpdateProductRequest;
use App\Modules\Catalog\Http\Resources\ProductResource;
use App\Modules\Catalog\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /**
     * List all products
     */
    public function index(Request $request): JsonResponse
    {
        $query = Product::with('category');

        // Filter by active status
        if ($request->has('active')) {
            $query->where('is_active', $request->boolean('active'));
        }

        // Filter by category
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->integer('category_id'));
        }

        // Filter by stock status
        if ($request->has('in_stock')) {
            if ($request->boolean('in_stock')) {
                $query->inStock();
            } else {
                $query->outOfStock();
            }
        }

        // Filter by low stock
        if ($request->boolean('low_stock')) {
            $query->lowStock();
        }

        // Search by name or SKU
        if ($request->filled('search')) {
            $search = $request->string('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        // Sorting
        $sortBy = $request->string('sort_by', 'created_at');
        $sortDir = $request->string('sort_dir', 'desc');
        $query->orderBy($sortBy, $sortDir);

        // Pagination
        $perPage = min($request->integer('per_page', 15), 100);
        $products = $query->paginate($perPage);

        return response()->json([
            'data' => ProductResource::collection($products),
            'meta' => [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
            ],
        ]);
    }

    /**
     * Show a single product
     */
    public function show(Product $product): JsonResponse
    {
        $product->load('category');

        return response()->json([
            'data' => new ProductResource($product),
        ]);
    }

    /**
     * Create a new product
     */
    public function store(StoreProductRequest $request, CreateProduct $action): JsonResponse
    {
        $product = $action->execute(
            $request->validated(),
            $request->file('image'),
            $request->user()->id
        );

        $product->load('category');

        return response()->json([
            'message' => 'Product created successfully.',
            'data' => new ProductResource($product),
        ], 201);
    }

    /**
     * Update a product
     */
    public function update(UpdateProductRequest $request, Product $product, UpdateProduct $action): JsonResponse
    {
        $this->ensureValidJson($request);
        // Note: For Products, we don't strictly enforce ensureDataNotEmpty() here 
        // because sometimes the action handles complex logic or file-only updates.
        // But UpdateProduct request rules are 'sometimes', so empty data would be no-op.
        // It's safer to ensure something was passed.
        $this->ensureDataNotEmpty($request->validated() + ($request->hasFile('image') ? ['image' => true] : []));

        $product = $action->execute(
            $product,
            $request->validated(),
            $request->file('image')
        );

        $product->load('category');

        return response()->json([
            'message' => 'Product updated successfully.',
            'data' => new ProductResource($product),
        ]);
    }

    /**
     * Soft delete a product
     */
    public function destroy(Request $request, Product $product, DeleteProduct $action): JsonResponse
    {
        // Only admin or manager can delete
        if (!$request->user()->isAdminOrManager()) {
            abort(403, 'Unauthorized');
        }

        $action->execute($product);

        return response()->json([
            'message' => 'Product deleted successfully.',
        ]);
    }
}
