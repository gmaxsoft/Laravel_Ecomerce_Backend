<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Product;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @OA\Tag(
 *     name="Products",
 *     description="API endpoints do zarządzania produktami"
 * )
 */
class ProductController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/products",
     *     summary="Lista produktów",
     *     tags={"Products"},
     *     @OA\Parameter(
     *         name="category",
     *         in="query",
     *         description="Filtruj po kategorii",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="size",
     *         in="query",
     *         description="Filtruj po rozmiarze",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="brand",
     *         in="query",
     *         description="Filtruj po marce",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Wyszukaj po nazwie",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="sort_by",
     *         in="query",
     *         description="Sortuj po (created_at, price, name)",
     *         required=false,
     *         @OA\Schema(type="string", default="created_at")
     *     ),
     *     @OA\Parameter(
     *         name="sort_order",
     *         in="query",
     *         description="Kierunek sortowania (asc, desc)",
     *         required=false,
     *         @OA\Schema(type="string", default="desc")
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Liczba produktów na stronę",
     *         required=false,
     *         @OA\Schema(type="integer", default=15)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lista produktów",
     *         @OA\JsonContent()
     *     )
     * )
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
     * @OA\Post(
     *     path="/api/admin/products",
     *     summary="Utwórz produkt",
     *     tags={"Products"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "price", "stock_quantity"},
     *             @OA\Property(property="name", type="string", example="Koszulka T-Shirt"),
     *             @OA\Property(property="description", type="string", example="Opis produktu"),
     *             @OA\Property(property="price", type="number", format="float", example=99.99),
     *             @OA\Property(property="sale_price", type="number", format="float", example=79.99),
     *             @OA\Property(property="category", type="string", example="Odzież"),
     *             @OA\Property(property="size", type="string", example="M"),
     *             @OA\Property(property="condition", type="string", enum={"excellent", "good", "fair"}, example="good"),
     *             @OA\Property(property="brand", type="string", example="Nike"),
     *             @OA\Property(property="color", type="string", example="Czarny"),
     *             @OA\Property(property="images", type="array", @OA\Items(type="string")),
     *             @OA\Property(property="stock_quantity", type="integer", example=10),
     *             @OA\Property(property="sku", type="string", example="SKU-12345")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Produkt utworzony pomyślnie",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="data", ref="#/components/schemas/Product")
     *         )
     *     ),
     *     @OA\Response(response=422, description="Błąd walidacji")
     * )
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
     * @OA\Get(
     *     path="/api/products/{id}",
     *     summary="Szczegóły produktu",
     *     tags={"Products"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID produktu",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Szczegóły produktu",
     *         @OA\JsonContent(ref="#/components/schemas/Product")
     *     ),
     *     @OA\Response(response=404, description="Produkt nie znaleziony")
     * )
     */
    public function show(string $id): ProductResource
    {
        $product = Product::where('is_active', true)->findOrFail($id);

        return new ProductResource($product);
    }

    /**
     * @OA\Put(
     *     path="/api/admin/products/{id}",
     *     summary="Aktualizuj produkt",
     *     tags={"Products"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/Product")
     *     ),
     *     @OA\Response(response=200, description="Produkt zaktualizowany"),
     *     @OA\Response(response=404, description="Produkt nie znaleziony")
     * )
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
     * @OA\Delete(
     *     path="/api/admin/products/{id}",
     *     summary="Usuń produkt",
     *     tags={"Products"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Produkt usunięty"),
     *     @OA\Response(response=404, description="Produkt nie znaleziony")
     * )
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
