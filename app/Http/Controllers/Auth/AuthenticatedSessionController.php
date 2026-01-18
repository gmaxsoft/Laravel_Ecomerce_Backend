<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

/**
 * @OA\Tag(
 *     name="Authentication",
 *     description="API endpoints do uwierzytelniania"
 * )
 * @OA\PathItem(path="/api/auth/login")
 * @OA\PathItem(path="/api/auth/logout")
 */
class AuthenticatedSessionController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/auth/login",
     *     summary="Logowanie użytkownika",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email", "password"},
     *             @OA\Property(property="email", type="string", format="email", example="user@example.com"),
     *             @OA\Property(property="password", type="string", format="password", example="password123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="Zalogowany pomyślnie"
     *     ),
     *     @OA\Response(response=422, description="Błąd walidacji"),
     *     @OA\Response(response=401, description="Nieprawidłowe dane logowania")
     * )
     *
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): Response
    {
        $request->authenticate();

        $request->session()->regenerate();

        return response()->noContent();
    }

    /**
     * @OA\Post(
     *     path="/api/auth/logout",
     *     summary="Wylogowanie użytkownika",
     *     tags={"Authentication"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=204,
     *         description="Wylogowany pomyślnie"
     *     ),
     *     @OA\Response(response=401, description="Nieautoryzowany")
     * )
     *
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): Response
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return response()->noContent();
    }
}
