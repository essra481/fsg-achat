<?php

namespace App\Http\Controllers;

use App\Services\TwoFactorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Nombre maximum d'échecs avant blocage temporaire.
     */
    private const MAX_ATTEMPTS = 5;

    /**
     * Durée du blocage (en secondes) après trop d'échecs.
     */
    private const LOCK_SECONDS = 300;

    /**
     * Hash bcrypt factice : permet de "perdre le même temps" que pour un vrai
     * compte quand l'email n'existe pas (évite de deviner quels emails existent).
     */
    private const DUMMY_HASH = '$2y$12$uuL.snVKRdgRkoWdOun0deocE7U7APmkEdj.MuymUIafBCPRFDPU.';

    public function __construct(private TwoFactorService $twoFactor) {}

    public function showLogin()
    {
        return view('login');
    }

    /**
     * Connexion (email + mot de passe), avec limitation des tentatives.
     * Si la double authentification est activée, on passe à l'étape 2.
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $throttleKey = Str::lower($credentials['email']) . '|' . $request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, self::MAX_ATTEMPTS)) {
            throw ValidationException::withMessages([
                'email' => 'Trop de tentatives de connexion. Réessayez dans '
                    . RateLimiter::availableIn($throttleKey) . ' secondes.',
            ]);
        }

        $provider = Auth::guard()->getProvider();

        $user = $provider->retrieveByCredentials(['email' => $credentials['email']]);

        if (! $user) {
            Hash::check($credentials['password'], self::DUMMY_HASH);
        }

        if (! $user || ! $provider->validateCredentials($user, $credentials)) {
            RateLimiter::hit($throttleKey, self::LOCK_SECONDS);

            return back()->withErrors([
                'email' => 'L’adresse email ou le mot de passe est incorrect.',
            ])->onlyInput('email');
        }

        RateLimiter::clear($throttleKey);

        // --- Étape 2 : double authentification -----------------------------
        if ($user->hasTwoFactorEnabled()) {
            $request->session()->regenerate();

            $request->session()->put('two_factor.user_id', $user->getAuthIdentifier());

            return redirect()->route('two-factor.challenge');
        }

        Auth::login($user);

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    /**
     * Page "code de double authentification".
     */
    public function showChallenge(Request $request)
    {
        if (! $request->session()->has('two_factor.user_id')) {
            return redirect()->route('login');
        }

        return view('auth.two-factor-challenge');
    }

    /**
     * Vérifie le code (application) ou le code de secours.
     */
    public function verifyChallenge(Request $request)
    {
        $userId = $request->session()->get('two_factor.user_id');

        if (! $userId) {
            return redirect()->route('login');
        }

        $request->validate([
            'code' => ['nullable', 'string', 'max:20'],
            'recovery_code' => ['nullable', 'string', 'max:30'],
        ]);

        $throttleKey = 'two-factor-login|' . $userId . '|' . $request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, self::MAX_ATTEMPTS)) {
            // Trop d'échecs : on annule la connexion en cours.
            $request->session()->forget('two_factor.user_id');

            return redirect()->route('login')->withErrors([
                'email' => 'Trop de tentatives. Réessayez dans '
                    . RateLimiter::availableIn($throttleKey) . ' secondes.',
            ]);
        }

        $user = Auth::guard()->getProvider()->retrieveById($userId);

        $input = (string) ($request->input('code') ?: $request->input('recovery_code'));

        if (! $user || ! $this->twoFactor->verifyCodeOrRecovery($user, $input)) {
            RateLimiter::hit($throttleKey, self::LOCK_SECONDS);

            return back()->withErrors([
                'code' => 'Le code est invalide ou a expiré.',
            ]);
        }

        RateLimiter::clear($throttleKey);

        $request->session()->forget('two_factor.user_id');

        Auth::login($user);

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    /**
     * Déconnexion (POST + CSRF).
     */
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
