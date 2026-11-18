<?php

namespace Tests\Feature\Metier;

use App\Models\Article;
use App\Models\consultation;
use App\Models\demande;
use App\Models\offre;
use Carbon\Carbon;

/**
 * Règles métier : une demande d'achat génère une consultation ;
 * la consultation contient la liste des articles à acheter.
 */
class DemandeConsultationTest extends MetierTestCase
{
    private function donneesDemande(array $surcharge = []): array
    {
        return array_merge([
            'reference' => '15/2026',
            'objet' => 'Achat de papier A4',
            'date_demande' => '2026-09-01',
            'service_demandeur' => 'Scolarité',
        ], $surcharge);
    }

    public function test_creer_une_demande_cree_automatiquement_la_consultation(): void
    {
        $reponse = $this->post(route('demande.store'), $this->donneesDemande());

        $demande = demande::firstOrFail();
        $consultation = consultation::firstOrFail();

        $this->assertSame(1, demande::count());
        $this->assertSame(1, consultation::count());

        // La consultation est rattachée à la demande et reprend ses informations
        $this->assertSame($demande->id, (int) $consultation->demande_achat_id);
        $this->assertSame('15/2026', $consultation->reference);
        $this->assertSame('Achat de papier A4', $consultation->objet);

        // Date de lancement = date de la demande ; date limite = +8 jours
        $this->assertSame('2026-09-01', Carbon::parse($consultation->date_lancement)->toDateString());
        $this->assertSame('2026-09-09', Carbon::parse($consultation->date_limite)->toDateString());

        // On arrive directement sur la consultation créée
        $reponse->assertRedirect(route('consultation.show', $consultation->id));
        $reponse->assertSessionHas('success');
    }

    public function test_les_champs_de_la_demande_sont_obligatoires(): void
    {
        $this->post(route('demande.store'), [])
            ->assertSessionHasErrors(['reference', 'objet', 'date_demande', 'service_demandeur']);

        $this->post(route('demande.store'), $this->donneesDemande(['date_demande' => 'pas-une-date']))
            ->assertSessionHasErrors('date_demande');

        $this->assertSame(0, demande::count());
        $this->assertSame(0, consultation::count());
    }

    public function test_modifier_la_demande_met_a_jour_reference_et_objet_de_la_consultation(): void
    {
        $demande = $this->creerDemande(['reference' => '10/2026']);
        $consultation = $this->creerConsultation([], $demande);

        $this->put(route('demande.update', $demande), $this->donneesDemande([
            'reference' => '10-bis/2026',
            'objet' => 'Nouvel objet',
        ]))->assertRedirect(route('demande.index'));

        $consultation->refresh();

        $this->assertSame('10-bis/2026', $consultation->reference);
        $this->assertSame('Nouvel objet', $consultation->objet);

        // Les dates de la consultation ne sont pas écrasées
        $this->assertSame('2026-09-09', Carbon::parse($consultation->date_limite)->toDateString());
    }

    public function test_supprimer_une_demande_supprime_tout_ce_qui_en_depend(): void
    {
        $demande = $this->creerDemande();
        $consultation = $this->creerConsultation([], $demande);
        $article = $this->creerArticle($consultation, 'Stylo', 10);
        $fournisseur = $this->creerFournisseur('Alpha');
        $this->inscrire($consultation, $fournisseur);
        $this->creerOffre($consultation, $article, $fournisseur, 1.2);

        $this->delete(route('demande.destroy', $demande))
            ->assertRedirect(route('demande.index'));

        $this->assertSame(0, demande::count());
        $this->assertSame(0, consultation::count());
        $this->assertSame(0, Article::count());
        $this->assertSame(0, offre::count());

        // Le fournisseur, lui, reste dans le catalogue
        $this->assertDatabaseHas('fournisseurs', ['name' => 'Alpha']);
    }

    public function test_la_liste_des_demandes_et_des_consultations_s_affiche(): void
    {
        $this->creerConsultation();

        $this->get(route('demande.index'))->assertOk()->assertSee('12/2026');
        $this->get(route('consultation.index'))->assertOk()->assertSee('12/2026');
    }

    public function test_la_liste_des_consultations_est_paginee_par_10(): void
    {
        for ($i = 1; $i <= 12; $i++) {
            $this->creerConsultation(['reference' => "$i/2026"], $this->creerDemande(['reference' => "$i/2026"]));
        }

        $this->get(route('consultation.index'))
            ->assertOk()
            ->assertViewHas('consultation', fn ($liste) => $liste->count() === 10 && $liste->total() === 12);

        $this->get(route('consultation.index', ['page' => 2]))
            ->assertViewHas('consultation', fn ($liste) => $liste->count() === 2);
    }

    public function test_une_consultation_ne_se_cree_que_depuis_une_demande(): void
    {
        // Les pages "créer" / "enregistrer" d'une consultation renvoient à la liste
        $this->get(route('consultation.create'))->assertRedirect(route('consultation.index'));
        $this->post(route('consultation.store'), ['objet' => 'x'])->assertRedirect(route('consultation.index'));

        $this->assertSame(0, consultation::count());
    }

    public function test_modifier_une_consultation(): void
    {
        $consultation = $this->creerConsultation();

        $this->put(route('consultation.update', $consultation), [
            'objet' => 'Objet corrigé',
            'date_lancement' => '2026-10-01',
            'date_limite' => '2026-10-15',
        ])->assertRedirect(route('consultation.index'));

        $consultation->refresh();
        $this->assertSame('Objet corrigé', $consultation->objet);
        $this->assertSame('2026-10-15', Carbon::parse($consultation->date_limite)->toDateString());

        // L'objet est obligatoire ; une date vide est enregistrée comme "non renseignée"
        $this->put(route('consultation.update', $consultation), ['objet' => ''])
            ->assertSessionHasErrors('objet');

        $this->put(route('consultation.update', $consultation), [
            'objet' => 'Objet', 'date_lancement' => '', 'date_limite' => '',
        ]);
        $this->assertNull($consultation->fresh()->date_limite);
    }

    public function test_supprimer_une_consultation_supprime_ses_articles(): void
    {
        $consultation = $this->creerConsultation();
        $this->creerArticle($consultation, 'Stylo', 10);

        $this->delete(route('consultation.destroy', $consultation))
            ->assertRedirect(route('consultation.index'));

        $this->assertSame(0, consultation::count());
        $this->assertSame(0, Article::count());
    }

    // ------------------------------------------------------------------
    // Articles de la consultation
    // ------------------------------------------------------------------

    public function test_enregistrer_les_articles_remplace_l_ancienne_liste(): void
    {
        $consultation = $this->creerConsultation();
        $this->creerArticle($consultation, 'Ancien article', 99);

        $this->post(route('consultation.saveArticles', $consultation), [
            'designation' => ['Stylo', '', 'Cahier'],
            'description' => ['Bleu', '', '96 pages'],
            'quantite' => ['10', '5', '7'],
        ])->assertRedirect(route('consultation.show', $consultation->id));

        $articles = Article::orderBy('id')->get();

        // La ligne vide est ignorée, l'ancien article est remplacé
        $this->assertCount(2, $articles);
        $this->assertSame(['Stylo', 'Cahier'], $articles->pluck('designation')->all());
        $this->assertSame([10, 7], $articles->pluck('quantite')->map(fn ($q) => (int) $q)->all());
        $this->assertSame(['Bleu', '96 pages'], $articles->pluck('description')->all());
    }

    public function test_vider_la_liste_des_articles(): void
    {
        $consultation = $this->creerConsultation();
        $this->creerArticle($consultation, 'Stylo', 10);

        $this->post(route('consultation.saveArticles', $consultation), [])
            ->assertRedirect(route('consultation.show', $consultation->id));

        $this->assertSame(0, Article::count());
    }

    public function test_la_fiche_consultation_affiche_les_articles(): void
    {
        $consultation = $this->creerConsultation();
        $this->creerArticle($consultation, 'Agrafeuse métallique', 4);

        $this->get(route('consultation.show', $consultation))
            ->assertOk()
            ->assertSee('Agrafeuse métallique');
    }

    public function test_les_articles_d_une_consultation_ne_se_melangent_pas_avec_une_autre(): void
    {
        $a = $this->creerConsultation(['reference' => '1/2026'], $this->creerDemande(['reference' => '1/2026']));
        $b = $this->creerConsultation(['reference' => '2/2026'], $this->creerDemande(['reference' => '2/2026']));

        $this->post(route('consultation.saveArticles', $a), ['designation' => ['Pour A'], 'quantite' => ['1']]);
        $this->post(route('consultation.saveArticles', $b), ['designation' => ['Pour B'], 'quantite' => ['2']]);

        $this->assertSame(['Pour A'], $a->articles()->pluck('designation')->all());
        $this->assertSame(['Pour B'], $b->articles()->pluck('designation')->all());
    }
}
