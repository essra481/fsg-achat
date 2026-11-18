<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * L'application est désormais protégée : un visiteur non connecté
     * qui ouvre la page d'accueil est redirigé vers la page de connexion.
     */
    public function test_guest_is_redirected_to_login_from_home(): void
    {
        $response = $this->get('/');

        $response->assertRedirect(route('login'));
    }
}
