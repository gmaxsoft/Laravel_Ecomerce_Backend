<?php

use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\CouponController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\ProductController;

use App\Http\Controllers\Api\WebhookController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\SocialAuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user();
});

// Trasy autentykacji API
Route::prefix('auth')->group(function () {
    Route::post('/register', [RegisteredUserController::class, 'store'])
        ->middleware('guest');

    Route::post('/login', [AuthenticatedSessionController::class, 'store'])
        ->middleware('guest');

    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
        ->middleware('auth:sanctum');

    // Social Authentication - Google
    Route::get('/google/redirect', [SocialAuthController::class, 'redirectToGoogle'])
        ->middleware('guest')
        ->name('google.api.redirect');

    Route::get('/google/callback', [SocialAuthController::class, 'handleGoogleCallback'])
        ->middleware('guest')
        ->name('google.api.callback');
});

// Trasy publiczne
Route::prefix('products')->group(function () {
    Route::get('/', [ProductController::class, 'index']);
    Route::get('/{id}', [ProductController::class, 'show']);
});

Route::prefix('coupons')->group(function () {
    Route::get('/', [CouponController::class, 'index']);
    Route::get('/{code}', [CouponController::class, 'show']);
    Route::post('/validate', [CouponController::class, 'validate'])->middleware('auth:sanctum');
});

// Trasy chronione (wymagają uwierzytelnienia)
Route::middleware('auth:sanctum')->group(function () {
    // Trasy koszyka
    Route::prefix('cart')->group(function () {
        Route::get('/', [CartController::class, 'index']);
        Route::post('/items', [CartController::class, 'addItem']);
        Route::put('/items/{id}', [CartController::class, 'updateItem']);
        Route::delete('/items/{id}', [CartController::class, 'removeItem']);
        Route::delete('/', [CartController::class, 'clear']);
    });

    // Trasy zamówień
    Route::prefix('orders')->group(function () {
        Route::get('/', [OrderController::class, 'index']);
        Route::post('/', [OrderController::class, 'store']);
        Route::get('/{id}', [OrderController::class, 'show']);
    });

    // Trasy administratora (do zarządzania produktami - mogą być chronione middleware admin)
    Route::prefix('admin/products')->group(function () {
        Route::post('/', [ProductController::class, 'store']);
        Route::put('/{id}', [ProductController::class, 'update']);
        Route::delete('/{id}', [ProductController::class, 'destroy']);
    });
});

// Webhooks Stripe (bez middleware auth - weryfikacja przez sygnaturę)
Route::post('/webhooks/stripe', [WebhookController::class, 'handleWebhook']);
