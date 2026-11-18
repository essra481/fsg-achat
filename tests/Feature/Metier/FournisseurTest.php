<?php

namespace Tests\Feature\Metier;

use App\Models\fournisseurs;
use App\Models\offre;

/**
 * Règles métier : catalogue des fournisseurs.
 */
class FournisseurTest extends MetierTestCase
{
    public function test_creer_un_fournisseur_avec_le_seul_nom(): void
    {
        $this->post(route('fournisseurs.store'), [
            'name' => 'Papeterie Alpha',
            'matricule_fiscal' => '',
            'email' => '',
            'telephone' => '',
        ])->assertRedirect(route('fournisseurs.index'));

        $fournisseur = fournisseurs::firstOrFail();

        $this->assertSame('Papeterie Alpha', $fournisseur->name);
        // Les champs laissés vides sont enregistrés comme "non renseignés" (NULL)
        $this->assertNull($fournisseur->matricule_fiscal);
        $this->assertNull($fournisseur->email);
        $this->assertNull($fournisseur->telephone);
    }

    public function test_le_nom_est_obligatoire(): void
    {
        $this->post(route('fournisseurs.store'), ['name' => ''])
            ->assertSessionHasErrors(['name' => 'Le nom du fournisseur est obligatoire.']);

        $this->assertSame(0, fournisseurs::count());
    }

    public function test_deux_fournisseurs_ne_peuvent_pas_partager_les_memes_identifiants(): void
    {
        $this->creerFournisseur('Alpha', [
            'matricule_fiscal' => '1234567A',
            'email' => 'alpha@example.tn',
            'telephone' => '71000000',
        ]);

        $this->post(route('fournisseurs.store'), ['name' => 'Alpha'])
            ->assertSessionHasErrors(['name' => 'Ce nom de fournisseur existe déjà.']);

        $this->post(route('fournisseurs.store'), ['name' => 'Beta', 'matricule_fiscal' => '1234567A'])
            ->assertSessionHasErrors(['matricule_fiscal' => 'Ce matricule fiscal existe déjà.']);

        $this->post(route('fournisseurs.store'), ['name' => 'Gamma', 'email' => 'alpha@example.tn'])
            ->assertSessionHasErrors(['email' => 'Cette adresse email existe déjà.']);

        $this->post(route('fournisseurs.store'), ['name' => 'Delta', 'telephone' => '71000000'])
            ->assertSessionHasErrors(['telephone' => 'Ce numéro de téléphone existe déjà.']);

        $this->post(route('fournisseurs.store'), ['name' => 'Epsilon', 'email' => 'pas-un-email'])
            ->assertSessionHasErrors(['email' => 'Veuillez saisir une adresse email valide.']);

        $this->assertSame(1, fournisseurs::count());
    }

    public function test_deux_fournisseurs_sans_matricule_peuvent_coexister(): void
    {
        $this->post(route('fournisseurs.store'), ['name' => 'Sans matricule 1']);
        $this->post(route('fournisseurs.store'), ['name' => 'Sans matricule 2']);

        $this->assertSame(2, fournisseurs::count());
    }

    public function test_modifier_un_fournisseur_en_gardant_ses_propres_valeurs(): void
    {
        $fournisseur = $this->creerFournisseur('Alpha', [
            'matricule_fiscal' => '1234567A',
            'email' => 'alpha@example.tn',
            'telephone' => '71000000',
        ]);

        // Son propre email / matricule ne compte pas comme un doublon
        $this->put(route('fournisseurs.update', $fournisseur), [
            'name' => 'Alpha Papeterie',
            'matricule_fiscal' => '1234567A',
            'email' => 'alpha@example.tn',
            'telephone' => '71000000',
        ])->assertRedirect(route('fournisseurs.index'));

        $this->assertSame('Alpha Papeterie', $fournisseur->fresh()->name);
    }

    public function test_modifier_un_fournisseur_avec_l_email_d_un_autre_est_refuse(): void
    {
        $this->creerFournisseur('Alpha', [
            'matricule_fiscal' => 'AAA', 'email' => 'alpha@example.tn', 'telephone' => '1',
        ]);
        $beta = $this->creerFournisseur('Beta', [
            'matricule_fiscal' => 'BBB', 'email' => 'beta@example.tn', 'telephone' => '2',
        ]);

        $this->put(route('fournisseurs.update', $beta), [
            'name' => 'Beta',
            'matricule_fiscal' => 'BBB',
            'email' => 'alpha@example.tn',
            'telephone' => '2',
        ])->assertSessionHasErrors('email');

        $this->assertSame('beta@example.tn', $beta->fresh()->email);
    }

    public function test_supprimer_un_fournisseur_supprime_ses_offres_et_ses_inscriptions(): void
    {
        $consultation = $this->creerConsultation();
        $article = $this->creerArticle($consultation, 'Stylo', 10);
        $alpha = $this->creerFournisseur('Alpha');
        $beta = $this->creerFournisseur('Beta');
        $this->inscrire($consultation, $alpha, $beta);
        $this->creerOffre($consultation, $article, $alpha, 1.0);
        $this->creerOffre($consultation, $article, $beta, 2.0);

        $this->delete(route('fournisseurs.destroy', $alpha))
            ->assertRedirect(route('fournisseurs.index'));

        $this->assertDatabaseMissing('fournisseurs', ['name' => 'Alpha']);
        $this->assertSame(1, offre::count());
        $this->assertSame([$beta->id], $consultation->fournisseurs()->pluck('fournisseurs.id')->all());
    }

    public function test_la_liste_des_fournisseurs_est_paginee_par_10(): void
    {
        for ($i = 1; $i <= 11; $i++) {
            $this->creerFournisseur("Fournisseur $i");
        }

        $this->get(route('fournisseurs.index'))
            ->assertOk()
            ->assertViewHas('fournisseurs', fn ($liste) => $liste->count() === 10 && $liste->total() === 11);
    }
}
