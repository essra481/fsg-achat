<?php

namespace Tests\Feature\Metier;

use App\Models\Signataire;
use Carbon\Carbon;

/**
 * Règles métier : les signataires (le Doyen signe les documents officiels).
 */
class SignataireTest extends MetierTestCase
{
    private const TYPE_DOYEN = 'العميد';

    private function creerSignataire(array $attributs = []): Signataire
    {
        return Signataire::create(array_merge([
            'nom' => 'Pr. Ahmed Ben Ali',
            'fonction' => 'Doyen de la faculté',
            'type' => self::TYPE_DOYEN,
            'actif' => true,
        ], $attributs));
    }

    public function test_un_nouveau_signataire_est_actif(): void
    {
        $this->post(route('signataires.store'), [
            'nom' => 'Pr. Ahmed Ben Ali',
            'fonction' => 'Doyen',
            'type' => self::TYPE_DOYEN,
            'actif' => '0',   // même si le formulaire essaie d'envoyer "inactif"
        ])->assertRedirect(route('signataires.index'));

        $this->assertTrue(Signataire::firstOrFail()->actif);
    }

    public function test_nom_fonction_et_type_sont_obligatoires(): void
    {
        $this->post(route('signataires.store'), [])
            ->assertSessionHasErrors(['nom', 'fonction', 'type']);

        $this->assertSame(0, Signataire::count());
    }

    public function test_la_date_de_fin_ne_peut_pas_preceder_la_date_de_debut(): void
    {
        $this->post(route('signataires.store'), [
            'nom' => 'X', 'fonction' => 'Y', 'type' => 'Z',
            'date_debut' => '2026-09-10',
            'date_fin' => '2026-09-01',
        ])->assertSessionHasErrors('date_fin');
    }

    public function test_modifier_un_signataire_permet_de_le_desactiver(): void
    {
        $signataire = $this->creerSignataire();

        // Case "actif" décochée : le champ n'est pas envoyé
        $this->put(route('signataires.update', $signataire), [
            'nom' => 'Pr. Ahmed Ben Ali',
            'fonction' => 'Doyen',
            'type' => self::TYPE_DOYEN,
        ])->assertRedirect(route('signataires.index'));

        $this->assertFalse($signataire->fresh()->actif);

        // Case cochée
        $this->put(route('signataires.update', $signataire), [
            'nom' => 'Pr. Ahmed Ben Ali',
            'fonction' => 'Doyen',
            'type' => self::TYPE_DOYEN,
            'actif' => '1',
        ]);

        $this->assertTrue($signataire->fresh()->actif);
    }

    public function test_supprimer_un_signataire_le_desactive_sans_le_perdre(): void
    {
        $signataire = $this->creerSignataire();

        $this->delete(route('signataires.destroy', $signataire))
            ->assertRedirect(route('signataires.index'));

        $signataire->refresh();

        $this->assertFalse($signataire->actif);
        $this->assertSame(Carbon::today()->toDateString(), $signataire->date_fin->toDateString());
        $this->assertSame(1, Signataire::count());
    }

    public function test_la_suppression_definitive_efface_le_signataire(): void
    {
        $signataire = $this->creerSignataire();

        $this->delete(route('signataires.forceDelete', $signataire))
            ->assertRedirect(route('signataires.index'));

        $this->assertSame(0, Signataire::count());
    }

    public function test_les_documents_utilisent_le_doyen_actif(): void
    {
        $consultation = $this->creerConsultation();
        $this->creerSignataire(['nom' => 'Ancien Doyen', 'actif' => false]);
        $this->creerSignataire(['nom' => 'Doyen Actuel', 'actif' => true]);
        $this->creerSignataire(['nom' => 'Chef de service', 'type' => 'رئيس مصلحة', 'actif' => true]);

        $this->get(route('consultation.show', $consultation))
            ->assertOk()
            ->assertViewHas('doyen', fn ($doyen) => $doyen !== null && $doyen->nom === 'Doyen Actuel');
    }

    public function test_sans_doyen_actif_les_documents_s_affichent_quand_meme(): void
    {
        $consultation = $this->creerConsultation();
        $this->creerSignataire(['actif' => false]);

        $this->get(route('consultation.show', $consultation))
            ->assertOk()
            ->assertViewHas('doyen', null);
    }

    public function test_la_liste_des_signataires_s_affiche(): void
    {
        $this->creerSignataire();

        $this->get(route('signataires.index'))->assertOk()->assertSee('Pr. Ahmed Ben Ali');
    }
}
