<?php

namespace App\Http\Controllers\Api;

use App\Cart;
use App\Coupon;
use App\Http\Resources\OrderResource;
use App\Order;
use App\OrderItem;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Stripe\Stripe;
use Stripe\PaymentIntent;
use Stripe\Exception\ApiErrorException;


class OrderController
{
    protected $inventoryService;

    public function __construct(InventoryService $inventoryService)
    {
        $this->inventoryService = $inventoryService;
    }

    
    public function index(Request $request): AnonymousResourceCollection
    {
        $user = $request->user();

        $orders = Order::with(['items', 'coupon'])
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return OrderResource::collection($orders);
    }

    
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'cart_id' => 'required|exists:carts,id',
            'coupon_code' => 'nullable|exists:coupons,code',
            'shipping_name' => 'required|string|max:255',
            'shipping_email' => 'required|email|max:255',
            'shipping_phone' => 'nullable|string|max:50',
            'shipping_address' => 'required|string',
            'shipping_city' => 'required|string|max:255',
            'shipping_postal_code' => 'required|string|max:20',
            'shipping_country' => 'required|string|max:100',
        ]);

        $cart = Cart::with('items.product')
            ->where('user_id', $user->id)
            ->where('id', $validated['cart_id'])
            ->whereNull('deleted_at')
            ->firstOrFail();

        if ($cart->items->isEmpty()) {
            return response()->json([
                'message' => 'Cart is empty',
            ], 400);
        }

        // Sprawdzenie dostępności produktów i rezerwacja stanu magazynowego
        foreach ($cart->items as $cartItem) {
            if (!$this->inventoryService->checkAvailability($cartItem->product, $cartItem->quantity)) {
                return response()->json([
                    'message' => 'Product ' . $cartItem->product->name . ' is out of stock',
                ], 400);
            }

            if (!$this->inventoryService->reserveStock($cartItem->product, $cartItem->quantity)) {
                return response()->json([
                    'message' => 'Failed to reserve stock for ' . $cartItem->product->name,
                ], 400);
            }
        }

        DB::beginTransaction();
        try {
            // Obliczanie sum
            $subtotal = $cart->total;
            $coupon = null;
            $discount = 0;

            if (!empty($validated['coupon_code'])) {
                $coupon = Coupon::where('code', $validated['coupon_code'])->first();
                if ($coupon && $coupon->isValid($user->id, $subtotal)) {
                    $discount = $coupon->calculateDiscount($subtotal);
                    $coupon->increment('usage_count');
                } else {
                    DB::rollBack();
                    return response()->json([
                        'message' => 'Invalid or expired coupon code',
                    ], 400);
                }
            }

            $tax = $subtotal * 0.10; // 10% podatku (przykład)
            $shipping = 0; // Darmowa dostawa (może być obliczona na podstawie reguł)
            $total = $subtotal + $tax + $shipping - $discount;

            // Tworzenie zamówienia
            $order = Order::create([
                'user_id' => $user->id,
                'status' => 'pending',
                'subtotal' => $subtotal,
                'tax' => $tax,
                'shipping' => $shipping,
                'discount' => $discount,
                'total' => $total,
                'coupon_id' => $coupon?->id,
                'shipping_name' => $validated['shipping_name'],
                'shipping_email' => $validated['shipping_email'],
                'shipping_phone' => $validated['shipping_phone'] ?? null,
                'shipping_address' => $validated['shipping_address'],
                'shipping_city' => $validated['shipping_city'],
                'shipping_postal_code' => $validated['shipping_postal_code'],
                'shipping_country' => $validated['shipping_country'],
                'payment_status' => 'pending',
            ]);

            // Tworzenie pozycji zamówienia
            foreach ($cart->items as $cartItem) {
                $product = $cartItem->product;

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'product_sku' => $product->sku,
                    'quantity' => $cartItem->quantity,
                    'price' => $cartItem->price,
                    'subtotal' => $cartItem->subtotal,
                ]);
            }

            // Tworzenie Stripe Payment Intent
            $paymentIntent = null;
            try {
                Stripe::setApiKey(config('services.stripe.secret'));

                $paymentIntent = PaymentIntent::create([
                    'amount' => (int)($total * 100), // Stripe używa centów
                    'currency' => config('cashier.currency', 'usd'),
                    'metadata' => [
                        'order_id' => $order->id,
                        'order_number' => $order->order_number,
                        'user_id' => $user->id,
                    ],
                    'description' => 'Order #' . $order->order_number,
                ]);

                // Zapisanie payment_intent_id w zamówieniu
                $order->update([
                    'stripe_payment_intent_id' => $paymentIntent->id,
                    'payment_method' => 'stripe',
                ]);

                Log::info('Stripe Payment Intent created', [
                    'order_id' => $order->id,
                    'payment_intent_id' => $paymentIntent->id,
                ]);
            } catch (ApiErrorException $e) {
                Log::error('Failed to create Stripe Payment Intent', [
                    'order_id' => $order->id,
                    'error' => $e->getMessage(),
                ]);

                // Zwolnienie rezerwacji stanu magazynowego w przypadku błędu
                foreach ($cart->items as $cartItem) {
                    $this->inventoryService->releaseReservedStock($cartItem->product, $cartItem->quantity);
                }

                DB::rollBack();
                return response()->json([
                    'message' => 'Failed to create payment intent',
                    'error' => $e->getMessage(),
                ], 500);
            }

            // Czyszczenie koszyka
            $cart->items()->delete();
            $cart->delete();

            DB::commit();

            return response()->json([
                'message' => 'Order created successfully',
                'data' => new OrderResource($order->load(['items', 'coupon'])),
                'payment_intent' => [
                    'client_secret' => $paymentIntent->client_secret,
                    'id' => $paymentIntent->id,
                ],
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to create order',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    
    public function show(string $id, Request $request): OrderResource
    {
        $user = $request->user();

        $order = Order::with(['items', 'coupon'])
            ->where('user_id', $user->id)
            ->findOrFail($id);

        return new OrderResource($order);
    }
}
