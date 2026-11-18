<?php

namespace App\Services;

use App\Models\user;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Logique "double authentification" liée à un utilisateur :
 * vérification d'un code TOTP (avec anti-rejeu) et codes de secours.
 */
class TwoFactorService
{
    public function __construct(private TotpService $totp) {}

    public function totp(): TotpService
    {
        return $this->totp;
    }

    /**
     * Vérifie un code TOTP. Un même code ne peut servir qu'une seule fois.
     */
    public function verifyCode(user $user, string $code): bool
    {
        if (! $user->two_factor_secret) {
            return false;
        }

        $step = $this->totp->verify($user->two_factor_secret, $code);

        if ($step === null) {
            return false;
        }

        // Anti-rejeu : Cache::add() renvoie false si la clé existe déjà.
        return Cache::add(
            'two-factor-used:' . $user->getKey() . ':' . $step,
            true,
            now()->addMinutes(3)
        );
    }

    /**
     * Génère 8 codes de secours (en clair, à montrer une seule fois).
     *
     * @return array<int, string>
     */
    public function generateRecoveryCodes(): array
    {
        $codes = [];

        for ($i = 0; $i < 8; $i++) {
            $codes[] = Str::lower(Str::random(5) . '-' . Str::random(5));
        }

        return $codes;
    }

    /**
     * Enregistre les codes de secours (hachés) pour l'utilisateur.
     *
     * @param  array<int, string>  $codes
     */
    public function storeRecoveryCodes(user $user, array $codes): void
    {
        $user->forceFill([
            'two_factor_recovery_codes' => json_encode(
                array_map(fn ($code) => Hash::make($code), $codes)
            ),
        ])->save();
    }

    /**
     * Utilise un code de secours : s'il est valide il est consommé.
     */
    public function useRecoveryCode(user $user, string $code): bool
    {
        $code = Str::lower(trim($code));

        if ($code === '' || ! $user->two_factor_recovery_codes) {
            return false;
        }

        $hashes = json_decode($user->two_factor_recovery_codes, true);

        if (! is_array($hashes)) {
            return false;
        }

        foreach ($hashes as $index => $hash) {
            if (Hash::check($code, $hash)) {
                unset($hashes[$index]);

                $user->forceFill([
                    'two_factor_recovery_codes' => json_encode(array_values($hashes)),
                ])->save();

                return true;
            }
        }

        return false;
    }

    /**
     * Accepte soit un code TOTP à 6 chiffres, soit un code de secours.
     */
    public function verifyCodeOrRecovery(user $user, string $input): bool
    {
        $input = trim($input);

        if ($input === '') {
            return false;
        }

        if (preg_match('/^\d{3}\s?\d{3}$/', $input)) {
            return $this->verifyCode($user, $input);
        }

        return $this->useRecoveryCode($user, $input);
    }
}
