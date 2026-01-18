<?php

namespace App\Http\Controllers\Api;

use App\Coupon;
use App\Http\Resources\CouponResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CouponController
{
    /**
     * Sprawdza poprawność kodu kuponu i zwraca informacje o rabacie
     */
    public function validate(Request $request): JsonResponse
    {
        $request->validate([
            'code' => 'required|string|exists:coupons,code',
            'amount' => 'nullable|numeric|min:0',
        ]);

        $user = $request->user();
        $amount = $request->input('amount');

        $coupon = Coupon::where('code', $request->code)->first();

        if (!$coupon) {
            return response()->json([
                'valid' => false,
                'errors' => ['Kupon nie został znaleziony'],
            ], 404);
        }

        // Używamy metody validate() która zwraca szczegółowe błędy
        $validation = $coupon->validate($user?->id, $amount);

        if (!$validation['valid']) {
            return response()->json([
                'valid' => false,
                'errors' => $validation['errors'],
                'coupon' => new CouponResource($coupon),
            ], 400);
        }

        // Oblicz rabat dla podanej kwoty
        $discount = 0;
        if ($amount) {
            $discount = $coupon->calculateDiscount($amount);
        }

        return response()->json([
            'valid' => true,
            'coupon' => new CouponResource($coupon),
            'discount' => round($discount, 2),
            'discount_formatted' => number_format($discount, 2, ',', ' ') . ' zł',
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/coupons",
     *     summary="Lista kuponów",
     *     tags={"Coupons"},
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Liczba kuponów na stronę",
     *         required=false,
     *         @OA\Schema(type="integer", default=15)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lista kuponów",
     *         @OA\JsonContent()
     *     )
     * )
     *
     * Wyświetla wszystkie aktywne kupony
     */
    public function index(Request $request)
    {
        $coupons = Coupon::where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>=', now());
            })
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return CouponResource::collection($coupons);
    }

    /**
     * @OA\Get(
     *     path="/api/coupons/{code}",
     *     summary="Szczegóły kuponu",
     *     tags={"Coupons"},
     *     @OA\Parameter(
     *         name="code",
     *         in="path",
     *         required=true,
     *         description="Kod kuponu",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Szczegóły kuponu",
     *         @OA\JsonContent(ref="#/components/schemas/Coupon")
     *     ),
     *     @OA\Response(response=404, description="Kupon nie znaleziony")
     * )
     *
     * Wyświetla szczegóły kuponu
     */
    public function show(string $code): CouponResource|JsonResponse
    {
        $coupon = Coupon::where('code', $code)->first();

        if (!$coupon) {
            return response()->json([
                'message' => 'Kupon nie został znaleziony',
            ], 404);
        }

        return new CouponResource($coupon);
    }
}
