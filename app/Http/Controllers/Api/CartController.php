<?php

namespace App\Http\Controllers\Api;

use App\Cart;
use App\CartItem;
use App\Http\Resources\CartResource;
use App\Product;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;


class CartController
{
    
    public function index(Request $request): CartResource
    {
        $user = $request->user();

        $cart = Cart::with('items.product')
            ->where('user_id', $user->id)
            ->whereNull('deleted_at')
            ->latest()
            ->first();

        if (!$cart) {
            $cart = Cart::create(['user_id' => $user->id]);
        }

        return new CartResource($cart->load('items.product'));
    }

    
    public function addItem(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
        ]);

        $product = Product::findOrFail($validated['product_id']);

        if ($product->available_stock < $validated['quantity']) {
            return response()->json([
                'message' => 'Insufficient stock available',
                'available_stock' => $product->available_stock,
            ], 400);
        }

        DB::beginTransaction();
        try {
            $cart = Cart::firstOrCreate(
                ['user_id' => $user->id, 'deleted_at' => null],
                ['user_id' => $user->id]
            );

            $cartItem = CartItem::updateOrCreate(
                [
                    'cart_id' => $cart->id,
                    'product_id' => $product->id,
                ],
                [
                    'quantity' => DB::raw('quantity + ' . $validated['quantity']),
                    'price' => $product->current_price,
                ]
            );

            // Odświeżenie, aby uzyskać zaktualizowaną ilość
            $cartItem->refresh();

            // Rezerwacja stanu magazynowego
            $product->increment('reserved_quantity', $validated['quantity']);

            DB::commit();

            return response()->json([
                'message' => 'Item added to cart successfully',
                'data' => new CartResource($cart->load('items.product')),
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to add item to cart',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    
    public function updateItem(Request $request, string $itemId): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'quantity' => 'required|integer|min:1',
        ]);

        $cartItem = CartItem::whereHas('cart', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })->with('product')->findOrFail($itemId);

        $product = $cartItem->product;
        $quantityDifference = $validated['quantity'] - $cartItem->quantity;

        if ($product->available_stock < $quantityDifference) {
            return response()->json([
                'message' => 'Insufficient stock available',
                'available_stock' => $product->available_stock,
            ], 400);
        }

        DB::beginTransaction();
        try {
            $cartItem->update([
                'quantity' => $validated['quantity'],
                'price' => $product->current_price,
            ]);

            // Aktualizacja zarezerwowanego stanu magazynowego
            if ($quantityDifference > 0) {
                $product->increment('reserved_quantity', $quantityDifference);
            } else {
                $product->decrement('reserved_quantity', abs($quantityDifference));
            }

            DB::commit();

            return response()->json([
                'message' => 'Cart item updated successfully',
                'data' => new CartResource($cartItem->cart->load('items.product')),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to update cart item',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    
    public function removeItem(string $itemId): JsonResponse
    {
        $user = request()->user();

        $cartItem = CartItem::whereHas('cart', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })->with('product')->findOrFail($itemId);

        $product = $cartItem->product;
        $cart = $cartItem->cart;

        DB::beginTransaction();
        try {
            // Zwolnienie zarezerwowanego stanu magazynowego
            $product->decrement('reserved_quantity', $cartItem->quantity);

            $cartItem->delete();

            DB::commit();

            return response()->json([
                'message' => 'Item removed from cart successfully',
                'data' => new CartResource($cart->load('items.product')),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to remove item from cart',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    
    public function clear(Request $request): JsonResponse
    {
        $user = $request->user();

        $cart = Cart::with('items.product')
            ->where('user_id', $user->id)
            ->whereNull('deleted_at')
            ->latest()
            ->first();

        if ($cart) {
            DB::beginTransaction();
            try {
                // Zwolnienie wszystkich zarezerwowanych stanów magazynowych
                foreach ($cart->items as $item) {
                    $item->product->decrement('reserved_quantity', $item->quantity);
                }

                $cart->items()->delete();

                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json([
                    'message' => 'Failed to clear cart',
                    'error' => $e->getMessage(),
                ], 500);
            }
        }

        return response()->json([
            'message' => 'Cart cleared successfully',
        ]);
    }
}
