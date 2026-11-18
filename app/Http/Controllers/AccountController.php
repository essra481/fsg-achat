<?php

namespace App\Http\Controllers;

use App\Services\TwoFactorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class AccountController extends Controller
{
    public function __construct(private TwoFactorService $twoFactor) {}

    /**
     * Afficher la page Mon compte
     */
    public function profile()
    {
        return view('account.profile');
    }

    /**
     * Modifier l'adresse email
     *
     * Sécurité : le mot de passe actuel est exigé (et le code de double
     * authentification si elle est activée). Sans cela, quelqu'un qui
     * accède à une session ouverte pourrait changer l'email puis prendre
     * le contrôle du compte.
     */
    public function updateEmail(Request $request)
    {
        $user = auth()->user();

        $validated = $request->validate([
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'email_password' => [
                'required',
                'current_password',
            ],
        ], [
            'email_password.required' => 'Le mot de passe actuel est obligatoire pour modifier l\'email.',
            'email_password.current_password' => 'Le mot de passe actuel est incorrect.',
        ]);

        $this->requireSecondFactor($request, 'email_code');

        $user->update([
            'email' => $validated['email'],
        ]);

        return back()->with('success', 'Votre adresse email a été modifiée avec succès.');
    }

    /**
     * Modifier le mot de passe
     */
    public function updatePassword(Request $request)
    {
        $user = auth()->user();

        $validated = $request->validate([
            'current_password' => [
                'required',
                'current_password',
            ],

            'password' => [
                'required',
                'confirmed',
                'different:current_password',
                Password::min(12)
                    ->mixedCase()
                    ->numbers()
                    ->symbols(),
            ],
        ], [
            'password.different' => 'Le nouveau mot de passe doit être différent de l\'actuel.',
        ]);

        $this->requireSecondFactor($request, 'password_code');

        $user->update([
            'password' => Hash::make($validated['password']),
            'must_change_password' => false,
        ]);

        // Déconnecte toutes les AUTRES sessions ouvertes avec l'ancien mot de passe.
        Auth::logoutOtherDevices($validated['password']);

        return back()->with('success', 'Votre mot de passe a été modifié avec succès.');
    }

    /**
     * Première connexion : page de configuration obligatoire
     * (le middleware ForcePasswordChange redirige ici).
     */
    public function change()
    {
        if (! auth()->user()->must_change_password) {
            return redirect()->route('dashboard');
        }

        return view('account.change');
    }

    /**
     * Première connexion : enregistrer le nouvel email + mot de passe.
     */
    public function update(Request $request)
    {
        $user = auth()->user();

        $validated = $request->validate([
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'password' => [
                'required',
                'confirmed',
                Password::min(12)
                    ->mixedCase()
                    ->numbers()
                    ->symbols(),
            ],
        ]);

        if (Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'password' => 'Le nouveau mot de passe doit être différent de l\'ancien.',
            ]);
        }

        $user->update([
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'must_change_password' => false,
        ]);

        Auth::logoutOtherDevices($validated['password']);

        return redirect()
            ->route('dashboard')
            ->with('success', 'Votre compte a été configuré avec succès.');
    }

    /**
     * Si la double authentification est activée, exige un code valide
     * (code de l'application ou code de secours) pour une action sensible.
     */
    private function requireSecondFactor(Request $request, string $field): void
    {
        $user = $request->user();

        if (! $user->hasTwoFactorEnabled()) {
            return;
        }

        $key = 'sensitive-2fa|' . $user->getAuthIdentifier();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages([
                $field => 'Trop de tentatives. Réessayez dans '
                    . RateLimiter::availableIn($key) . ' secondes.',
            ]);
        }

        if (! $this->twoFactor->verifyCodeOrRecovery($user, (string) $request->input($field, ''))) {
            RateLimiter::hit($key, 300);

            throw ValidationException::withMessages([
                $field => 'Le code de double authentification est invalide.',
            ]);
        }

        RateLimiter::clear($key);
    }
}
