<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

/**
 * @OA\Tag(
 *     name="Authentication",
 *     description="API endpoints do uwierzytelniania"
 * )
 * @OA\PathItem(path="/api/auth/google/redirect")
 * @OA\PathItem(path="/api/auth/google/callback")
 */
class SocialAuthController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/auth/google/redirect",
     *     summary="Przekierowanie do Google OAuth",
     *     tags={"Authentication"},
     *     @OA\Response(
     *         response=302,
     *         description="Przekierowanie do Google"
     *     )
     * )
     *
     * Przekierowanie do Google OAuth
     */
    public function redirectToGoogle(): RedirectResponse
    {
        return Socialite::driver('google')->redirect();
    }

    /**
     * @OA\Get(
     *     path="/api/auth/google/callback",
     *     summary="Obsługa callback z Google OAuth",
     *     tags={"Authentication"},
     *     @OA\Response(
     *         response=200,
     *         description="Zalogowany pomyślnie przez Google",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="user", ref="#/components/schemas/User"),
     *             @OA\Property(property="token", type="string")
     *         )
     *     ),
     *     @OA\Response(response=500, description="Błąd uwierzytelniania")
     * )
     *
     * Obsługa callback z Google OAuth
     */
    public function handleGoogleCallback(): JsonResponse|RedirectResponse
    {
        try {
            $googleUser = Socialite::driver('google')->user();

            // Sprawdź czy użytkownik już istnieje
            $user = User::where('email', $googleUser->getEmail())->first();

            if (!$user) {
                // Utwórz nowego użytkownika
                $user = User::create([
                    'name' => $googleUser->getName(),
                    'email' => $googleUser->getEmail(),
                    'provider' => 'google',
                    'provider_id' => $googleUser->getId(),
                    'avatar' => $googleUser->getAvatar(),
                    'email_verified_at' => now(), // Google zweryfikował email
                    'password' => null, // Brak hasła dla social login
                ]);
            } else {
                // Aktualizuj dane użytkownika jeśli używa Google
                if ($user->provider !== 'google') {
                    $user->update([
                        'provider' => 'google',
                        'provider_id' => $googleUser->getId(),
                        'avatar' => $googleUser->getAvatar() ?? $user->avatar,
                    ]);
                }
            }

            // Logowanie użytkownika
            Auth::login($user);

            // Dla API - zwróć token Sanctum
            if (request()->expectsJson()) {
                $token = $user->createToken('auth-token')->plainTextToken;

                return response()->json([
                    'message' => 'Login successful',
                    'user' => $user,
                    'token' => $token,
                ]);
            }

            // Dla web - przekieruj
            return redirect()->intended(config('app.frontend_url') . '/dashboard');
        } catch (\Exception $e) {
            \Log::error('Google OAuth error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            if (request()->expectsJson()) {
                return response()->json([
                    'message' => 'Authentication failed',
                    'error' => $e->getMessage(),
                ], 500);
            }

            return redirect()->route('login')->with('error', 'Authentication failed');
        }
    }
}
