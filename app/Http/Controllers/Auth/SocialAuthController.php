<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class SocialAuthController extends Controller
{
    /**
     * Przekierowanie do Google OAuth
     */
    public function redirectToGoogle(): RedirectResponse
    {
        return Socialite::driver('google')->redirect();
    }

    /**
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
