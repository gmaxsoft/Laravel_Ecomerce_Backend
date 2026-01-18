<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\ProductResource;
use App\Product;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProductController
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Product::query()->where('is_active', true);

        // Filtrowanie po kategorii
        if ($request->has('category')) {
            $query->where('category', $request->category);
        }

        // Filtrowanie po rozmiarze
        if ($request->has('size')) {
            $query->where('size', $request->size);
        }

        // Filtrowanie po marce
        if ($request->has('brand')) {
            $query->where('brand', $request->brand);
        }

        // Wyszukiwanie po nazwie
        if ($request->has('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        // Sortowanie
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $products = $query->paginate($request->get('per_page', 15));

        return ProductResource::collection($products);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'sale_price' => 'nullable|numeric|min:0',
            'category' => 'nullable|string|max:255',
            'size' => 'nullable|string|max:50',
            'condition' => 'nullable|in:excellent,good,fair',
            'brand' => 'nullable|string|max:255',
            'color' => 'nullable|string|max:100',
            'images' => 'nullable|array',
            'stock_quantity' => 'required|integer|min:0',
            'sku' => 'nullable|string|max:255|unique:products,sku',
        ]);

        $product = Product::create($validated);

        return response()->json([
            'message' => 'Product created successfully',
            'data' => new ProductResource($product),
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): ProductResource
    {
        $product = Product::where('is_active', true)->findOrFail($id);

        return new ProductResource($product);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $product = Product::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'price' => 'sometimes|numeric|min:0',
            'sale_price' => 'nullable|numeric|min:0',
            'category' => 'nullable|string|max:255',
            'size' => 'nullable|string|max:50',
            'condition' => 'nullable|in:excellent,good,fair',
            'brand' => 'nullable|string|max:255',
            'color' => 'nullable|string|max:100',
            'images' => 'nullable|array',
            'stock_quantity' => 'sometimes|integer|min:0',
            'sku' => 'nullable|string|max:255|unique:products,sku,' . $id,
            'is_active' => 'sometimes|boolean',
        ]);

        $product->update($validated);

        return response()->json([
            'message' => 'Product updated successfully',
            'data' => new ProductResource($product->fresh()),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): JsonResponse
    {
        $product = Product::findOrFail($id);
        $product->delete();

        return response()->json([
            'message' => 'Product deleted successfully',
        ]);
    }
}
