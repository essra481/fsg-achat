<?php

namespace App\Services;

/**
 * Implémentation TOTP (RFC 6238) sans dépendance externe.
 * Compatible Google Authenticator, Microsoft Authenticator, Authy, FreeOTP...
 * (SHA-1, 6 chiffres, pas de 30 secondes).
 */
class TotpService
{
    private const ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    private const PERIOD = 30;

    private const DIGITS = 6;

    /**
     * Génère un secret aléatoire (160 bits) encodé en Base32.
     */
    public function generateSecret(int $bytes = 20): string
    {
        return $this->base32Encode(random_bytes($bytes));
    }

    /**
     * Code à 6 chiffres pour un secret et un "time step" donnés.
     */
    public function code(string $secret, int $timeStep): string
    {
        $key = $this->base32Decode($secret);

        // Compteur sur 8 octets (big-endian)
        $counter = pack('N*', 0, $timeStep);

        $hash = hash_hmac('sha1', $counter, $key, true);

        $offset = ord($hash[19]) & 0x0F;

        $binary =
            ((ord($hash[$offset]) & 0x7F) << 24) |
            ((ord($hash[$offset + 1]) & 0xFF) << 16) |
            ((ord($hash[$offset + 2]) & 0xFF) << 8) |
            (ord($hash[$offset + 3]) & 0xFF);

        return str_pad(
            (string) ($binary % (10 ** self::DIGITS)),
            self::DIGITS,
            '0',
            STR_PAD_LEFT
        );
    }

    /**
     * Vérifie un code. Retourne le "time step" correspondant si le code est
     * valide (fenêtre de tolérance de ±1 période pour le décalage d'horloge),
     * sinon null. Le time step sert à empêcher la réutilisation d'un code.
     */
    public function verify(string $secret, string $code, int $window = 1, ?int $now = null): ?int
    {
        $code = preg_replace('/\s+/', '', $code) ?? '';

        if (! preg_match('/^\d{6}$/', $code)) {
            return null;
        }

        $current = intdiv($now ?? time(), self::PERIOD);

        for ($i = -$window; $i <= $window; $i++) {
            $step = $current + $i;

            if (hash_equals($this->code($secret, $step), $code)) {
                return $step;
            }
        }

        return null;
    }

    /**
     * URI "otpauth://" (utilisable pour générer un QR code ou saisie manuelle).
     */
    public function uri(string $secret, string $account, string $issuer): string
    {
        return 'otpauth://totp/'
            . rawurlencode($issuer . ':' . $account)
            . '?secret=' . $secret
            . '&issuer=' . rawurlencode($issuer)
            . '&algorithm=SHA1&digits=' . self::DIGITS
            . '&period=' . self::PERIOD;
    }

    private function base32Encode(string $data): string
    {
        $bits = '';

        foreach (str_split($data) as $char) {
            $bits .= str_pad(decbin(ord($char)), 8, '0', STR_PAD_LEFT);
        }

        $output = '';

        foreach (str_split($bits, 5) as $chunk) {
            $output .= self::ALPHABET[bindec(str_pad($chunk, 5, '0', STR_PAD_RIGHT))];
        }

        return $output;
    }

    private function base32Decode(string $secret): string
    {
        $secret = strtoupper(preg_replace('/[^A-Za-z2-7]/', '', $secret) ?? '');

        $bits = '';

        foreach (str_split($secret) as $char) {
            $bits .= str_pad(
                decbin((int) strpos(self::ALPHABET, $char)),
                5,
                '0',
                STR_PAD_LEFT
            );
        }

        $output = '';

        foreach (str_split($bits, 8) as $byte) {
            if (strlen($byte) === 8) {
                $output .= chr(bindec($byte));
            }
        }

        return $output;
    }
}
