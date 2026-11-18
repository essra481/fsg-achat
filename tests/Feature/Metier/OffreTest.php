<?php

namespace Tests\Feature\Metier;

use App\Models\offre;

/**
 * Règles métier : choix des fournisseurs consultés et saisie de leurs offres
 * (prix unitaire HT, ou "ND" = non disponible / "NC" = non concerné).
 */
class OffreTest extends MetierTestCase
{
    private function scenario(): array
    {
        $consultation = $this->creerConsultation();
        $stylo = $this->creerArticle($consultation, 'Stylo', 100);
        $cahier = $this->creerArticle($consultation, 'Cahier', 50);
        $alpha = $this->creerFournisseur('Alpha');
        $beta = $this->creerFournisseur('Beta');

        return [$consultation, $stylo, $cahier, $alpha, $beta];
    }

    private function urlSaisie($consultation, $fournisseur): string
    {
        return "/offre/{$consultation->id}/fournisseur/{$fournisseur->id}";
    }

    // ------------------------------------------------------------------
    // Sélection des fournisseurs consultés
    // ------------------------------------------------------------------

    public function test_choisir_les_fournisseurs_d_une_consultation(): void
    {
        [$consultation, , , $alpha, $beta] = $this->scenario();

        $this->get("/offre/{$consultation->id}/fournisseurs")->assertOk()->assertSee('Alpha');

        $this->post("/offre/{$consultation->id}/fournisseurs", ['fournisseurs' => [$alpha->id, $beta->id]])
            ->assertRedirect("/offre/{$consultation->id}/edit");

        $this->assertSame(2, $consultation->fournisseurs()->count());
    }

    public function test_choisir_deux_fois_le_meme_fournisseur_ne_le_duplique_pas(): void
    {
        [$consultation, , , $alpha, $beta] = $this->scenario();

        $this->post("/offre/{$consultation->id}/fournisseurs", ['fournisseurs' => [$alpha->id]]);
        $this->post("/offre/{$consultation->id}/fournisseurs", ['fournisseurs' => [$alpha->id, $beta->id]]);

        $this->assertSame(2, $consultation->fournisseurs()->count());
    }

    public function test_il_faut_choisir_au_moins_un_fournisseur_existant(): void
    {
        [$consultation] = $this->scenario();

        $this->post("/offre/{$consultation->id}/fournisseurs", [])
            ->assertSessionHasErrors(['fournisseurs' => 'Veuillez sélectionner au moins un fournisseur.']);

        $this->post("/offre/{$consultation->id}/fournisseurs", ['fournisseurs' => [9999]])
            ->assertSessionHasErrors('fournisseurs.0');

        $this->assertSame(0, $consultation->fournisseurs()->count());
    }

    // ------------------------------------------------------------------
    // Saisie des offres d'un fournisseur
    // ------------------------------------------------------------------

    public function test_saisir_les_prix_d_un_fournisseur(): void
    {
        [$consultation, $stylo, $cahier, $alpha] = $this->scenario();
        $this->inscrire($consultation, $alpha);

        $this->get($this->urlSaisie($consultation, $alpha))->assertOk();

        $this->post($this->urlSaisie($consultation, $alpha), [
            'article_id' => [$stylo->id, $cahier->id],
            'prix_unitaire_ht' => ['12,5', '8'],   // la virgule décimale est acceptée
        ])->assertRedirect("/offre/{$consultation->id}/edit");

        $offreStylo = offre::where('article_id', $stylo->id)->firstOrFail();
        $offreCahier = offre::where('article_id', $cahier->id)->firstOrFail();

        $this->assertEqualsWithDelta(12.5, (float) $offreStylo->prix_unitaire_ht, 0.0001);
        $this->assertEqualsWithDelta(8.0, (float) $offreCahier->prix_unitaire_ht, 0.0001);
        $this->assertNull($offreStylo->statut_prix);
        $this->assertSame($alpha->id, (int) $offreStylo->fournisseur_id);
    }

    public function test_nd_et_nc_sont_enregistres_comme_statut_sans_prix(): void
    {
        [$consultation, $stylo, $cahier, $alpha] = $this->scenario();
        $this->inscrire($consultation, $alpha);

        $this->post($this->urlSaisie($consultation, $alpha), [
            'article_id' => [$stylo->id, $cahier->id],
            'prix_unitaire_ht' => ['nd', 'NC'],   // la casse n'a pas d'importance
        ])->assertRedirect("/offre/{$consultation->id}/edit");

        $offreStylo = offre::where('article_id', $stylo->id)->firstOrFail();
        $offreCahier = offre::where('article_id', $cahier->id)->firstOrFail();

        $this->assertNull($offreStylo->prix_unitaire_ht);
        $this->assertSame('ND', $offreStylo->statut_prix);
        $this->assertNull($offreCahier->prix_unitaire_ht);
        $this->assertSame('NC', $offreCahier->statut_prix);
    }

    public function test_un_prix_invalide_annule_toute_la_saisie(): void
    {
        [$consultation, $stylo, $cahier, $alpha] = $this->scenario();
        $this->inscrire($consultation, $alpha);

        foreach (['abc', '-3', '1e3', '12.5.1'] as $mauvaisPrix) {
            $this->post($this->urlSaisie($consultation, $alpha), [
                'article_id' => [$stylo->id, $cahier->id],
                'prix_unitaire_ht' => ['10', $mauvaisPrix],   // le 1er prix est valide...
            ])->assertSessionHasErrors('prix_unitaire_ht');

            // ... mais RIEN n'est enregistré, pas même le prix valide
            $this->assertSame(0, offre::count(), "Le prix « $mauvaisPrix » aurait dû tout annuler.");
        }
    }

    public function test_resaisir_les_prix_met_a_jour_au_lieu_de_dupliquer(): void
    {
        [$consultation, $stylo, $cahier, $alpha] = $this->scenario();
        $this->inscrire($consultation, $alpha);

        $this->post($this->urlSaisie($consultation, $alpha), [
            'article_id' => [$stylo->id, $cahier->id],
            'prix_unitaire_ht' => ['10', '20'],
        ]);

        $this->post($this->urlSaisie($consultation, $alpha), [
            'article_id' => [$stylo->id, $cahier->id],
            'prix_unitaire_ht' => ['9', 'ND'],
        ]);

        $this->assertSame(2, offre::count());

        $offreStylo = offre::where('article_id', $stylo->id)->firstOrFail();
        $offreCahier = offre::where('article_id', $cahier->id)->firstOrFail();

        $this->assertEqualsWithDelta(9.0, (float) $offreStylo->prix_unitaire_ht, 0.0001);
        $this->assertNull($offreCahier->prix_unitaire_ht);
        $this->assertSame('ND', $offreCahier->statut_prix);
    }

    public function test_un_fournisseur_non_inscrit_a_la_consultation_est_refuse(): void
    {
        [$consultation, $stylo, , $alpha] = $this->scenario();
        // Alpha n'est PAS inscrit à cette consultation

        $this->get($this->urlSaisie($consultation, $alpha))->assertForbidden();

        $this->post($this->urlSaisie($consultation, $alpha), [
            'article_id' => [$stylo->id],
            'prix_unitaire_ht' => ['10'],
        ])->assertForbidden();

        $this->assertSame(0, offre::count());
    }

    public function test_un_article_inexistant_est_refuse(): void
    {
        [$consultation, , , $alpha] = $this->scenario();
        $this->inscrire($consultation, $alpha);

        $this->post($this->urlSaisie($consultation, $alpha), [
            'article_id' => [9999],
            'prix_unitaire_ht' => ['10'],
        ])->assertSessionHasErrors('article_id.0');

        $this->assertSame(0, offre::count());
    }

    public function test_autant_de_prix_que_d_articles_sont_exiges(): void
    {
        [$consultation, $stylo, $cahier, $alpha] = $this->scenario();
        $this->inscrire($consultation, $alpha);

        $this->post($this->urlSaisie($consultation, $alpha), [
            'article_id' => [$stylo->id, $cahier->id],
            'prix_unitaire_ht' => ['10'],
        ])->assertSessionHasErrors(['prix_unitaire_ht' => 'Les données des articles et des prix ne correspondent pas.']);

        $this->assertSame(0, offre::count());
    }

    // ------------------------------------------------------------------
    // Retirer un fournisseur
    // ------------------------------------------------------------------

    public function test_retirer_un_fournisseur_supprime_ses_offres_mais_pas_celles_des_autres(): void
    {
        [$consultation, $stylo, , $alpha, $beta] = $this->scenario();
        $this->inscrire($consultation, $alpha, $beta);
        $this->creerOffre($consultation, $stylo, $alpha, 1.0);
        $this->creerOffre($consultation, $stylo, $beta, 2.0);

        $this->delete($this->urlSaisie($consultation, $alpha))
            ->assertRedirect("/offre/{$consultation->id}/edit")
            ->assertSessionHas('success');

        $this->assertSame([$beta->id], $consultation->fournisseurs()->pluck('fournisseurs.id')->all());
        $this->assertSame(1, offre::count());
        $this->assertSame($beta->id, (int) offre::firstOrFail()->fournisseur_id);

        // Le fournisseur reste dans le catalogue
        $this->assertDatabaseHas('fournisseurs', ['id' => $alpha->id]);
    }

    public function test_retirer_un_fournisseur_qui_n_est_pas_dans_la_consultation_affiche_une_erreur(): void
    {
        [$consultation, , , $alpha] = $this->scenario();

        $this->delete($this->urlSaisie($consultation, $alpha))
            ->assertRedirect("/offre/{$consultation->id}/edit")
            ->assertSessionHas('error');
    }

    // ------------------------------------------------------------------
    // Pages
    // ------------------------------------------------------------------

    public function test_les_pages_des_offres_s_affichent_avec_des_donnees(): void
    {
        [$consultation, $stylo, , $alpha] = $this->scenario();
        $this->inscrire($consultation, $alpha);
        $this->creerOffre($consultation, $stylo, $alpha, 1.5);

        $this->get('/offre')->assertOk();
        $this->get("/offre/{$consultation->id}/edit")->assertOk()->assertSee('Alpha');
        $this->get("/offre/{$consultation->id}/show")->assertOk()->assertSee('Alpha');
    }
}
