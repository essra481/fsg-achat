<?php

namespace App\Http\Controllers;

use App\Services\TwoFactorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

/**
 * Gestion de la double authentification (activer / confirmer / désactiver /
 * régénérer les codes de secours) depuis la page "Mon compte".
 */
class TwoFactorController extends Controller
{
    public function __construct(private TwoFactorService $twoFactor) {}

    public function show(Request $request)
    {
        $user = $request->user();

        $pendingSecret = null;
        $otpUri = null;

        // Secret généré mais pas encore confirmé
        if (! $user->hasTwoFactorEnabled() && $user->two_factor_secret) {
            $pendingSecret = $user->two_factor_secret;

            $otpUri = $this->twoFactor->totp()->uri(
                $pendingSecret,
                $user->email,
                config('app.name', 'FSG ACHAT')
            );
        }

        return view('account.two-factor', [
            'enabled' => $user->hasTwoFactorEnabled(),
            'pendingSecret' => $pendingSecret,
            'otpUri' => $otpUri,
            'recoveryCodes' => session('recovery_codes'),
            'remainingCodes' => $user->two_factor_recovery_codes
                ? count(json_decode($user->two_factor_recovery_codes, true) ?: [])
                : 0,
        ]);
    }

    /**
     * Étape 1 : générer un secret (mot de passe exigé).
     */
    public function enable(Request $request)
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
        ], [
            'current_password.required' => 'Le mot de passe actuel est obligatoire.',
            'current_password.current_password' => 'Le mot de passe actuel est incorrect.',
        ]);

        $user = $request->user();

        if ($user->hasTwoFactorEnabled()) {
            return redirect()->route('two-factor.show');
        }

        $user->forceFill([
            'two_factor_secret' => $this->twoFactor->totp()->generateSecret(),
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ])->save();

        return redirect()->route('two-factor.show');
    }

    /**
     * Étape 2 : confirmer avec un premier code de l'application.
     */
    public function confirm(Request $request)
    {
        $request->validate([
            'code' => ['required', 'string', 'max:20'],
        ], [
            'code.required' => 'Saisissez le code à 6 chiffres.',
        ]);

        $user = $request->user();

        if ($user->hasTwoFactorEnabled() || ! $user->two_factor_secret) {
            return redirect()->route('two-factor.show');
        }

        $this->throttle($request, 'code');

        if (! $this->twoFactor->verifyCode($user, $request->input('code'))) {
            RateLimiter::hit($this->key($request), 300);

            throw ValidationException::withMessages([
                'code' => 'Code invalide. Vérifiez l\'heure de votre téléphone et réessayez.',
            ]);
        }

        RateLimiter::clear($this->key($request));

        $codes = $this->twoFactor->generateRecoveryCodes();

        $this->twoFactor->storeRecoveryCodes($user, $codes);

        $user->forceFill(['two_factor_confirmed_at' => now()])->save();

        return redirect()
            ->route('two-factor.show')
            ->with('recovery_codes', $codes)
            ->with('success', 'La double authentification est maintenant activée.');
    }

    /**
     * Annuler une activation non confirmée.
     */
    public function cancel(Request $request)
    {
        $user = $request->user();

        if (! $user->hasTwoFactorEnabled()) {
            $user->forceFill([
                'two_factor_secret' => null,
                'two_factor_recovery_codes' => null,
                'two_factor_confirmed_at' => null,
            ])->save();
        }

        return redirect()->route('two-factor.show');
    }

    /**
     * Générer de nouveaux codes de secours (mot de passe + code exigés).
     */
    public function regenerateRecoveryCodes(Request $request)
    {
        $user = $request->user();

        $this->assertPasswordAndCode($request);

        $codes = $this->twoFactor->generateRecoveryCodes();

        $this->twoFactor->storeRecoveryCodes($user, $codes);

        return redirect()
            ->route('two-factor.show')
            ->with('recovery_codes', $codes)
            ->with('success', 'De nouveaux codes de secours ont été générés. Les anciens ne fonctionnent plus.');
    }

    /**
     * Désactiver la double authentification (mot de passe + code exigés).
     */
    public function disable(Request $request)
    {
        $user = $request->user();

        $this->assertPasswordAndCode($request);

        $user->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ])->save();

        return redirect()
            ->route('two-factor.show')
            ->with('success', 'La double authentification a été désactivée.');
    }

    /**
     * Vérifie mot de passe actuel + code (application ou secours).
     */
    private function assertPasswordAndCode(Request $request): void
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'code' => ['required', 'string', 'max:30'],
        ], [
            'current_password.required' => 'Le mot de passe actuel est obligatoire.',
            'current_password.current_password' => 'Le mot de passe actuel est incorrect.',
            'code.required' => 'Le code de double authentification est obligatoire.',
        ]);

        $user = $request->user();

        if (! $user->hasTwoFactorEnabled()) {
            throw ValidationException::withMessages([
                'code' => 'La double authentification n\'est pas activée.',
            ]);
        }

        $this->throttle($request, 'code');

        if (! $this->twoFactor->verifyCodeOrRecovery($user, $request->input('code'))) {
            RateLimiter::hit($this->key($request), 300);

            throw ValidationException::withMessages([
                'code' => 'Le code de double authentification est invalide.',
            ]);
        }

        RateLimiter::clear($this->key($request));
    }

    private function key(Request $request): string
    {
        return 'two-factor-manage|' . $request->user()->getAuthIdentifier();
    }

    private function throttle(Request $request, string $field): void
    {
        if (RateLimiter::tooManyAttempts($this->key($request), 5)) {
            throw ValidationException::withMessages([
                $field => 'Trop de tentatives. Réessayez dans '
                    . RateLimiter::availableIn($this->key($request)) . ' secondes.',
            ]);
        }
    }
}
