<?php

namespace Tests\Feature\Metier;

use App\Models\procesverbal;

/**
 * Le tableau de bord résume l'activité.
 */
class DashboardTest extends MetierTestCase
{
    public function test_le_tableau_de_bord_affiche_les_compteurs(): void
    {
        $c1 = $this->creerConsultation(['reference' => '1/2026'], $this->creerDemande(['reference' => '1/2026']));
        $this->creerConsultation(['reference' => '2/2026'], $this->creerDemande(['reference' => '2/2026']));
        $this->creerFournisseur('Alpha');
        $this->creerFournisseur('Beta');
        $this->creerFournisseur('Gamma');
        procesverbal::create(['consultation_id' => $c1->id, 'date_creation' => now(), 'commentaire' => 'x']);

        $this->get('/dashboard')
            ->assertOk()
            ->assertViewHas('nbDemandes', 2)
            ->assertViewHas('nbConsultations', 2)
            ->assertViewHas('nbFournisseurs', 3)
            ->assertViewHas('nbProcesVerbaux', 1);
    }

    public function test_la_page_d_accueil_est_le_tableau_de_bord(): void
    {
        $this->get('/')->assertOk()->assertViewHas('nbDemandes', 0);
    }

    public function test_seules_les_5_dernieres_consultations_sont_listees(): void
    {
        for ($i = 1; $i <= 7; $i++) {
            $this->creerConsultation(['reference' => "$i/2026"], $this->creerDemande(['reference' => "$i/2026"]));
        }

        $this->get('/dashboard')
            ->assertViewHas('dernieresConsultations', fn ($liste) => $liste->count() === 5);
    }
}
