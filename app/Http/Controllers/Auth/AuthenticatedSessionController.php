<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;


class AuthenticatedSessionController extends Controller
{
    
    public function store(LoginRequest $request): JsonResponse|Response
    {
        $request->authenticate();

        // Dla API używamy Sanctum, dla web używamy sesji
        if ($request->expectsJson() || $request->is('api/*')) {
            $user = $request->user();
            $token = $user->createToken('api-token')->plainTextToken;

            return response()->json([
                'user' => $user,
                'token' => $token,
            ], 200);
        }

        $request->session()->regenerate();

        return response()->noContent();
    }

    
    public function destroy(Request $request): Response
    {
        // Dla API używamy Sanctum, dla web używamy sesji
        if ($request->expectsJson() || $request->is('api/*')) {
            $token = $request->user()->currentAccessToken();
            if ($token) {
                $token->delete();
            }
            return response()->noContent();
        }

        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return response()->noContent();
    }
}
