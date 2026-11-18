<?php

namespace Tests\Unit;

use App\Services\TotpService;
use PHPUnit\Framework\TestCase;

class TotpServiceTest extends TestCase
{
    /**
     * Vecteur de test officiel de la RFC 6238 (secret ASCII "12345678901234567890",
     * SHA-1). À T=59s le code à 8 chiffres est 94287082 => 6 chiffres : 287082.
     */
    private const RFC_SECRET = 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ';

    public function test_code_matches_rfc6238_vectors(): void
    {
        $totp = new TotpService();

        $this->assertSame('287082', $totp->code(self::RFC_SECRET, intdiv(59, 30)));
        $this->assertSame('081804', $totp->code(self::RFC_SECRET, intdiv(1111111109, 30)));
        $this->assertSame('050471', $totp->code(self::RFC_SECRET, intdiv(1111111111, 30)));
        $this->assertSame('005924', $totp->code(self::RFC_SECRET, intdiv(1234567890, 30)));
    }

    public function test_verify_accepts_current_and_adjacent_steps_only(): void
    {
        $totp = new TotpService();
        $now = 1111111109;

        $this->assertSame(intdiv($now, 30), $totp->verify(self::RFC_SECRET, '081804', 1, $now));
        // Période précédente et suivante tolérées (décalage d'horloge)
        $this->assertNotNull($totp->verify(self::RFC_SECRET, '081804', 1, $now + 30));
        $this->assertNotNull($totp->verify(self::RFC_SECRET, '081804', 1, $now - 30));
        // Trop loin : refusé
        $this->assertNull($totp->verify(self::RFC_SECRET, '081804', 1, $now + 120));
    }

    public function test_verify_rejects_malformed_codes(): void
    {
        $totp = new TotpService();

        foreach (['', 'abcdef', '12345', '1234567', '12 34'] as $bad) {
            $this->assertNull($totp->verify(self::RFC_SECRET, $bad, 1, 59));
        }
    }

    public function test_generated_secret_is_valid_base32_and_unique(): void
    {
        $totp = new TotpService();

        $a = $totp->generateSecret();
        $b = $totp->generateSecret();

        $this->assertMatchesRegularExpression('/^[A-Z2-7]{32}$/', $a);
        $this->assertNotSame($a, $b);
    }

    public function test_uri_format(): void
    {
        $uri = (new TotpService())->uri('ABCDEFGH', 'a@b.tn', 'FSG ACHAT');

        $this->assertStringStartsWith('otpauth://totp/FSG%20ACHAT%3Aa%40b.tn?secret=ABCDEFGH', $uri);
        $this->assertStringContainsString('issuer=FSG%20ACHAT', $uri);
    }
}
