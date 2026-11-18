<?php

namespace Tests\Feature;

use App\Models\user;
use App\Services\TotpService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SecurityTest extends TestCase
{
    private const PASSWORD = 'OldPassw0rd!Long';

    protected function setUp(): void
    {
        parent::setUp();

        // Base SQLite en mémoire, recréée à chaque test.
        Artisan::call('migrate', ['--force' => true]);
    }

    private function makeUser(array $attributes = []): user
    {
        return user::create(array_merge([
            'name' => 'Test',
            'email' => 'agent@example.com',
            'password' => Hash::make(self::PASSWORD),
            'must_change_password' => false,
        ], $attributes));
    }

    /** Active la double authentification et renvoie [secret, codes de secours]. */
    private function enableTwoFactor(user $user): array
    {
        $this->actingAs($user)
            ->post(route('two-factor.enable'), ['current_password' => self::PASSWORD])
            ->assertRedirect(route('two-factor.show'));

        $secret = $user->fresh()->two_factor_secret;
        $totp = app(TotpService::class);

        $response = $this->actingAs($user)
            ->post(route('two-factor.confirm'), [
                'code' => $totp->code($secret, intdiv(time(), 30)),
            ]);

        $response->assertRedirect(route('two-factor.show'));

        return [$secret, session('recovery_codes')];
    }

    // ------------------------------------------------------------------
    // 1. Routes protégées
    // ------------------------------------------------------------------

    public function test_guests_are_redirected_to_login_on_get_routes(): void
    {
        $urls = [
            '/', '/dashboard', '/consultation', '/consultations', '/demande',
            '/fournisseurs', '/offre', '/procesverbal', '/document', '/agents',
            '/signataires', '/imports/excel', '/account', '/account/two-factor',
            '/documents/repartition/1', '/documents/repartition/1/pdf',
            '/documents/1/excel', '/consultation/1', '/consultation/1/pdf',
            '/consultation/1/print', '/consultation/1/export-excel',
            '/consultations/1/excel', '/procesverbal/1/pdf', '/procesverbal/1/excel',
            '/offre/1/fournisseurs', '/offre/1/show', '/change-password',
        ];

        foreach ($urls as $url) {
            $this->get($url)->assertRedirect(route('login'));
        }
    }

    public function test_guests_cannot_write_or_delete(): void
    {
        $this->post('/consultation/1/articles', [])->assertRedirect(route('login'));
        $this->post('/demande', [])->assertRedirect(route('login'));
        $this->post('/fournisseurs', [])->assertRedirect(route('login'));
        $this->post('/procesverbal', [])->assertRedirect(route('login'));
        $this->post('/imports/excel/confirm')->assertRedirect(route('login'));
        $this->post('/account/email', [])->assertRedirect(route('login'));
        $this->post('/account/password', [])->assertRedirect(route('login'));
        $this->delete('/consultation/1')->assertRedirect(route('login'));
        $this->delete('/fournisseurs/1')->assertRedirect(route('login'));
        $this->delete('/signataires/1/force-delete')->assertRedirect(route('login'));
    }

    public function test_public_pages_stay_public(): void
    {
        $this->get('/login')->assertOk();
    }

    public function test_authenticated_user_can_open_main_pages(): void
    {
        $this->actingAs($this->makeUser());

        foreach (['/', '/dashboard', '/account', '/account/two-factor', '/consultation', '/demande', '/fournisseurs', '/signataires', '/imports/excel'] as $url) {
            $this->get($url)->assertOk();
        }
    }

    // ------------------------------------------------------------------
    // 2. Connexion / déconnexion
    // ------------------------------------------------------------------

    public function test_login_success(): void
    {
        $this->makeUser();

        $this->post('/login', ['email' => 'agent@example.com', 'password' => self::PASSWORD])
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticated();
    }

    public function test_login_failure_is_generic(): void
    {
        $this->makeUser();

        $this->from('/login')
            ->post('/login', ['email' => 'agent@example.com', 'password' => 'wrong'])
            ->assertSessionHasErrors('email');

        $this->from('/login')
            ->post('/login', ['email' => 'nobody@example.com', 'password' => 'wrong'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_login_is_locked_after_five_failures(): void
    {
        $this->makeUser();

        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['email' => 'agent@example.com', 'password' => 'wrong']);
        }

        // Même avec le BON mot de passe, le compte est bloqué temporairement
        $this->post('/login', ['email' => 'agent@example.com', 'password' => self::PASSWORD])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_logout(): void
    {
        $this->actingAs($this->makeUser())
            ->post('/logout')
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    // ------------------------------------------------------------------
    // 3. Première connexion (changement obligatoire)
    // ------------------------------------------------------------------

    public function test_first_login_forces_account_setup(): void
    {
        $user = $this->makeUser(['must_change_password' => true]);

        $this->actingAs($user)->get('/consultation')->assertRedirect(route('account.change'));
        $this->actingAs($user)->get('/dashboard')->assertRedirect(route('account.change'));
        $this->actingAs($user)->get(route('account.change'))->assertOk();
    }

    public function test_first_login_setup_rejects_weak_password_then_accepts_strong(): void
    {
        $user = $this->makeUser(['must_change_password' => true]);

        $this->actingAs($user)
            ->post(route('account.update'), [
                'email' => 'new@example.com',
                'password' => 'short',
                'password_confirmation' => 'short',
            ])->assertSessionHasErrors('password');

        // Réutiliser l'ancien mot de passe est refusé
        $this->actingAs($user)
            ->post(route('account.update'), [
                'email' => 'new@example.com',
                'password' => self::PASSWORD,
                'password_confirmation' => self::PASSWORD,
            ])->assertSessionHasErrors('password');

        $this->actingAs($user)
            ->post(route('account.update'), [
                'email' => 'new@example.com',
                'password' => 'BrandNew#Passw0rd!',
                'password_confirmation' => 'BrandNew#Passw0rd!',
            ])->assertRedirect(route('dashboard'));

        $user->refresh();
        $this->assertFalse($user->must_change_password);
        $this->assertSame('new@example.com', $user->email);
        $this->assertTrue(Hash::check('BrandNew#Passw0rd!', $user->password));

        $this->actingAs($user)->get('/dashboard')->assertOk();
    }

    public function test_setup_page_is_not_reachable_once_done(): void
    {
        $this->actingAs($this->makeUser())
            ->get(route('account.change'))
            ->assertRedirect(route('dashboard'));
    }

    // ------------------------------------------------------------------
    // 4. Changement d'email / de mot de passe
    // ------------------------------------------------------------------

    public function test_email_change_requires_current_password(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)
            ->post('/account/email', ['email' => 'x@example.com'])
            ->assertSessionHasErrors('email_password');

        $this->actingAs($user)
            ->post('/account/email', ['email' => 'x@example.com', 'email_password' => 'wrong'])
            ->assertSessionHasErrors('email_password');

        $this->assertSame('agent@example.com', $user->fresh()->email);

        $this->actingAs($user)
            ->post('/account/email', ['email' => 'x@example.com', 'email_password' => self::PASSWORD])
            ->assertSessionHasNoErrors();

        $this->assertSame('x@example.com', $user->fresh()->email);
    }

    public function test_email_must_be_unique_and_valid(): void
    {
        $user = $this->makeUser();
        $this->makeUser(['email' => 'other@example.com']);

        $this->actingAs($user)
            ->post('/account/email', ['email' => 'other@example.com', 'email_password' => self::PASSWORD])
            ->assertSessionHasErrors('email');

        $this->actingAs($user)
            ->post('/account/email', ['email' => 'not-an-email', 'email_password' => self::PASSWORD])
            ->assertSessionHasErrors('email');
    }

    public function test_password_change_rules(): void
    {
        $user = $this->makeUser();

        // mauvais mot de passe actuel
        $this->actingAs($user)->post('/account/password', [
            'current_password' => 'wrong',
            'password' => 'BrandNew#Passw0rd!',
            'password_confirmation' => 'BrandNew#Passw0rd!',
        ])->assertSessionHasErrors('current_password');

        // trop faible
        $this->actingAs($user)->post('/account/password', [
            'current_password' => self::PASSWORD,
            'password' => 'weak',
            'password_confirmation' => 'weak',
        ])->assertSessionHasErrors('password');

        // confirmation différente
        $this->actingAs($user)->post('/account/password', [
            'current_password' => self::PASSWORD,
            'password' => 'BrandNew#Passw0rd!',
            'password_confirmation' => 'Different#Passw0rd!',
        ])->assertSessionHasErrors('password');

        // identique à l'ancien
        $this->actingAs($user)->post('/account/password', [
            'current_password' => self::PASSWORD,
            'password' => self::PASSWORD,
            'password_confirmation' => self::PASSWORD,
        ])->assertSessionHasErrors('password');

        $this->assertTrue(Hash::check(self::PASSWORD, $user->fresh()->password));

        // OK
        $this->actingAs($user)->post('/account/password', [
            'current_password' => self::PASSWORD,
            'password' => 'BrandNew#Passw0rd!',
            'password_confirmation' => 'BrandNew#Passw0rd!',
        ])->assertSessionHasNoErrors();

        $this->assertTrue(Hash::check('BrandNew#Passw0rd!', $user->fresh()->password));
    }

    // ------------------------------------------------------------------
    // 5. Double authentification
    // ------------------------------------------------------------------

    public function test_enabling_two_factor_requires_password_and_valid_code(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)
            ->post(route('two-factor.enable'), ['current_password' => 'wrong'])
            ->assertSessionHasErrors('current_password');

        $this->assertNull($user->fresh()->two_factor_secret);

        $this->actingAs($user)
            ->post(route('two-factor.enable'), ['current_password' => self::PASSWORD]);

        $this->assertNotNull($user->fresh()->two_factor_secret);
        $this->assertFalse($user->fresh()->hasTwoFactorEnabled(), 'Pas actif tant que non confirmé');

        // Tant que le code n'est pas confirmé, la connexion reste simple
        $this->actingAs($user)->get(route('two-factor.show'))->assertOk()->assertSee('Étape 2');

        $this->actingAs($user)
            ->post(route('two-factor.confirm'), ['code' => '000000'])
            ->assertSessionHasErrors('code');

        $this->assertFalse($user->fresh()->hasTwoFactorEnabled());
    }

    public function test_full_two_factor_login_flow(): void
    {
        $user = $this->makeUser();

        [$secret, $recoveryCodes] = $this->enableTwoFactor($user);

        $this->assertTrue($user->fresh()->hasTwoFactorEnabled());
        $this->assertCount(8, $recoveryCodes);

        // Le secret est chiffré en base (jamais en clair)
        $raw = \DB::table('users')->where('id', $user->id)->value('two_factor_secret');
        $this->assertNotSame($secret, $raw);

        // Les codes de secours sont hachés en base
        $storedCodes = \DB::table('users')->where('id', $user->id)->value('two_factor_recovery_codes');
        $this->assertStringNotContainsString($recoveryCodes[0], $storedCodes);

        // --- Nouvelle session : connexion en deux étapes ---
        auth()->logout();
        $this->flushSession();

        $this->post('/login', ['email' => 'agent@example.com', 'password' => self::PASSWORD])
            ->assertRedirect(route('two-factor.challenge'));

        $this->assertGuest();
        $this->get('/dashboard')->assertRedirect(route('login'));
        $this->get(route('two-factor.challenge'))->assertOk();

        // Mauvais code
        $this->post(route('two-factor.verify'), ['code' => '123456'])
            ->assertSessionHasErrors('code');
        $this->assertGuest();

        // Bon code (pas de temps suivant, celui en cours a déjà servi)
        $totp = app(TotpService::class);
        $good = $totp->code($secret, intdiv(time(), 30) + 1);

        $this->post(route('two-factor.verify'), ['code' => $good])
            ->assertRedirect(route('dashboard'));
        $this->assertAuthenticated();

        // --- Anti-rejeu : le même code ne marche pas une 2e fois ---
        auth()->logout();
        $this->flushSession();

        $this->post('/login', ['email' => 'agent@example.com', 'password' => self::PASSWORD]);
        $this->post(route('two-factor.verify'), ['code' => $good])
            ->assertSessionHasErrors('code');
        $this->assertGuest();

        // --- Code de secours : marche une seule fois ---
        $this->post(route('two-factor.verify'), ['recovery_code' => $recoveryCodes[0]])
            ->assertRedirect(route('dashboard'));
        $this->assertAuthenticated();

        auth()->logout();
        $this->flushSession();

        $this->post('/login', ['email' => 'agent@example.com', 'password' => self::PASSWORD]);
        $this->post(route('two-factor.verify'), ['recovery_code' => $recoveryCodes[0]])
            ->assertSessionHasErrors('code');
        $this->assertGuest();
    }

    public function test_challenge_page_requires_first_step(): void
    {
        $this->get(route('two-factor.challenge'))->assertRedirect(route('login'));
        $this->post(route('two-factor.verify'), ['code' => '123456'])->assertRedirect(route('login'));
    }

    public function test_two_factor_challenge_is_rate_limited(): void
    {
        $user = $this->makeUser();
        $this->enableTwoFactor($user);

        auth()->logout();
        $this->flushSession();

        $this->post('/login', ['email' => 'agent@example.com', 'password' => self::PASSWORD]);

        for ($i = 0; $i < 5; $i++) {
            $this->post(route('two-factor.verify'), ['code' => '000000']);
        }

        // Après 5 échecs la connexion en cours est annulée
        $this->post(route('two-factor.verify'), ['code' => '000000'])
            ->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_sensitive_changes_require_code_when_two_factor_is_on(): void
    {
        $user = $this->makeUser();
        [$secret] = $this->enableTwoFactor($user);
        $totp = app(TotpService::class);

        // Email : mot de passe correct mais SANS code => refusé
        $this->actingAs($user)
            ->post('/account/email', ['email' => 'x@example.com', 'email_password' => self::PASSWORD])
            ->assertSessionHasErrors('email_code');
        $this->assertSame('agent@example.com', $user->fresh()->email);

        // Mot de passe : sans code => refusé
        $this->actingAs($user)->post('/account/password', [
            'current_password' => self::PASSWORD,
            'password' => 'BrandNew#Passw0rd!',
            'password_confirmation' => 'BrandNew#Passw0rd!',
        ])->assertSessionHasErrors('password_code');
        $this->assertTrue(Hash::check(self::PASSWORD, $user->fresh()->password));

        // Avec un code valide => accepté
        $this->actingAs($user)->post('/account/email', [
            'email' => 'x@example.com',
            'email_password' => self::PASSWORD,
            'email_code' => $totp->code($secret, intdiv(time(), 30) + 1),
        ])->assertSessionHasNoErrors();
        $this->assertSame('x@example.com', $user->fresh()->email);
    }

    public function test_disabling_two_factor_requires_password_and_code(): void
    {
        $user = $this->makeUser();
        [$secret] = $this->enableTwoFactor($user);
        $totp = app(TotpService::class);

        $this->actingAs($user)
            ->delete(route('two-factor.disable'), ['current_password' => self::PASSWORD, 'code' => '000000'])
            ->assertSessionHasErrors('code');
        $this->assertTrue($user->fresh()->hasTwoFactorEnabled());

        $this->actingAs($user)
            ->delete(route('two-factor.disable'), [
                'current_password' => self::PASSWORD,
                'code' => $totp->code($secret, intdiv(time(), 30) + 1),
            ])->assertSessionHasNoErrors();

        $fresh = $user->fresh();
        $this->assertFalse($fresh->hasTwoFactorEnabled());
        $this->assertNull($fresh->two_factor_secret);
        $this->assertNull($fresh->two_factor_recovery_codes);
    }

    public function test_two_factor_pages_render_in_every_state(): void
    {
        $user = $this->makeUser();

        // Les codes de secours sont montrés une fois, juste après l'activation
        $this->actingAs($user)
            ->post(route('two-factor.enable'), ['current_password' => self::PASSWORD]);

        $secret = $user->fresh()->two_factor_secret;

        $this->actingAs($user)
            ->post(route('two-factor.confirm'), [
                'code' => app(TotpService::class)->code($secret, intdiv(time(), 30)),
            ])
            ->assertSessionHas('recovery_codes');

        $this->actingAs($user)->get(route('two-factor.show'))
            ->assertOk()
            ->assertSee('Double authentification activée');

        // Les formulaires sensibles affichent le champ "code" quand la 2FA est active
        $this->actingAs($user)->get(route('account.profile'))
            ->assertOk()
            ->assertSee('email_code')
            ->assertSee('password_code');
    }

    public function test_logout_button_is_in_layout(): void
    {
        $this->actingAs($this->makeUser())
            ->get('/account')
            ->assertOk()
            ->assertSee('Déconnexion');
    }

    public function test_user_stays_logged_in_after_password_change_but_old_sessions_are_dropped(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)->post('/account/password', [
            'current_password' => self::PASSWORD,
            'password' => 'BrandNew#Passw0rd!',
            'password_confirmation' => 'BrandNew#Passw0rd!',
        ])->assertSessionHasNoErrors();

        // La session courante reste valide
        $this->get('/account')->assertOk();

        // Une autre session qui porte l'ancien mot de passe est rejetée
        $this->withSession(['password_hash_web' => 'hash-de-l-ancien-mot-de-passe'])
            ->get('/account')
            ->assertRedirect(route('login'));
    }

    public function test_import_preview_never_leaks_technical_errors(): void
    {
        \Illuminate\Support\Facades\Storage::fake('local');
        \Illuminate\Support\Facades\Storage::put('imports/bad.xlsx', 'x');

        // Erreur technique simulée (ex. SQL) : ne doit jamais atteindre l'utilisateur
        $this->mock(\App\Services\ProcurementImportService::class, function ($mock) {
            $mock->shouldReceive('preview')->andThrow(
                new \PDOException('SQLSTATE[42S02]: table secrete introuvable /var/www/achat/app')
            );
        });

        $this->actingAs($this->makeUser())
            ->withSession(['procurement_import_file' => [
                'path' => 'imports/bad.xlsx',
                'type' => 'consultation',
                'service' => 'x',
                'sheets' => ['Feuil1'],
            ]])
            ->from('/imports/excel/choose-sheet')
            ->post('/imports/excel/preview', ['sheet' => 'Feuil1'])
            ->assertSessionHasErrors('file');

        $message = session('errors')->first('file');

        $this->assertStringNotContainsString('SQLSTATE', $message);
        $this->assertStringNotContainsString('/var/www', $message);
        $this->assertStringContainsString('Impossible d’analyser', $message);
    }

    public function test_import_preview_keeps_business_messages(): void
    {
        \Illuminate\Support\Facades\Storage::fake('local');
        \Illuminate\Support\Facades\Storage::put('imports/bad.xlsx', 'x');

        $this->mock(\App\Services\ProcurementImportService::class, function ($mock) {
            $mock->shouldReceive('preview')->andThrow(
                new \RuntimeException('Import bloqué : la consultation « C9 » n’existe pas.')
            );
        });

        $this->actingAs($this->makeUser())
            ->withSession(['procurement_import_file' => [
                'path' => 'imports/bad.xlsx', 'type' => 'consultation',
                'service' => 'x', 'sheets' => ['Feuil1'],
            ]])
            ->from('/imports/excel/choose-sheet')
            ->post('/imports/excel/preview', ['sheet' => 'Feuil1'])
            ->assertSessionHasErrors('file');

        $this->assertStringContainsString('Import bloqué', session('errors')->first('file'));
    }

    // ------------------------------------------------------------------
    // 6. Validations ajoutées
    // ------------------------------------------------------------------

    public function test_procesverbal_store_requires_existing_consultation(): void
    {
        $this->actingAs($this->makeUser())
            ->post('/procesverbal', ['consultation_id' => 999])
            ->assertSessionHasErrors('consultation_id');
    }

    public function test_demande_store_validates_lengths(): void
    {
        $this->actingAs($this->makeUser())
            ->post('/demande', [
                'reference' => 'R1',
                'objet' => str_repeat('a', 1001),
                'date_demande' => '2026-09-30',
                'service_demandeur' => 'Info',
            ])->assertSessionHasErrors('objet');
    }

    public function test_save_articles_validates_input(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)
            ->post('/consultation/1/articles', [
                'designation' => ['ok'],
                'quantite' => ['abc'],
            ])->assertStatus(404); // consultation inexistante : binding avant validation

        // Base de données : une consultation existe → la validation s'applique
        \DB::table('demande')->insert([
            'id' => 1, 'objet' => 'x', 'date_demande' => '2026-09-30',
            'service_demandeur' => 'Info',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        \DB::table('consultation')->insert([
            'id' => 1, 'demande_achat_id' => 1, 'objet' => 'x',
            'date_lancement' => '2026-09-30', 'date_limite' => '2026-10-30',
            'reference' => 'C1',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        // Les migrations du projet ne créent pas la colonne "description" de la
        // table article (elle existe dans la vraie base) : on la simule ici.
        if (! Schema::hasColumn('article', 'description')) {
            Schema::table('article', function ($table) {
                $table->text('description')->nullable();
            });
        }

        // Un article existant doit survivre à une requête invalide
        \DB::table('article')->insert([
            'consultation_id' => 1, 'designation' => 'ancien', 'quantite' => 3,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->actingAs($user)
            ->post('/consultation/1/articles', [
                'designation' => ['ok'],
                'quantite' => ['abc'],
            ])->assertSessionHasErrors('quantite.0');

        $this->assertSame(1, \DB::table('article')->count());
        $this->assertSame('ancien', \DB::table('article')->value('designation'));

        // Requête valide : les articles sont remplacés
        $this->actingAs($user)
            ->post('/consultation/1/articles', [
                'designation' => ['stylo', 'cahier'],
                'quantite' => ['2', '5'],
            ])->assertSessionHasNoErrors();

        $this->assertSame(2, \DB::table('article')->count());
    }
}
