<?php

namespace Tests\Feature\Metier;

use App\Exports\ConsultationExport;
use App\Models\procesverbal;
use Barryvdh\Snappy\Facades\SnappyPdf;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Règles métier : le procès-verbal (PV) clôt une consultation.
 */
class ProcesVerbalTest extends MetierTestCase
{
    public function test_creer_un_pv_pour_une_consultation(): void
    {
        $consultation = $this->creerConsultation();

        $this->post(route('procesverbal.store'), [
            'consultation_id' => $consultation->id,
            'commentaire' => 'Séance du jury : offre la moins disante retenue.',
        ])->assertRedirect(route('procesverbal.index'))
            ->assertSessionHas('success');

        $pv = procesverbal::firstOrFail();

        $this->assertSame($consultation->id, (int) $pv->consultation_id);
        $this->assertSame('Séance du jury : offre la moins disante retenue.', $pv->commentaire);
        // La date de création est celle du jour
        $this->assertSame(Carbon::today()->toDateString(), Carbon::parse($pv->date_creation)->toDateString());
    }

    public function test_le_commentaire_du_pv_est_facultatif(): void
    {
        $consultation = $this->creerConsultation();

        $this->post(route('procesverbal.store'), ['consultation_id' => $consultation->id])
            ->assertSessionHasNoErrors();

        $this->assertNull(procesverbal::firstOrFail()->commentaire);
    }

    public function test_un_pv_exige_une_consultation_existante(): void
    {
        $this->post(route('procesverbal.store'), [])
            ->assertSessionHasErrors('consultation_id');

        $this->post(route('procesverbal.store'), ['consultation_id' => 9999])
            ->assertSessionHasErrors('consultation_id');

        $this->assertSame(0, procesverbal::count());
    }

    public function test_modifier_le_commentaire_d_un_pv(): void
    {
        $consultation = $this->creerConsultation();
        $pv = procesverbal::create([
            'consultation_id' => $consultation->id,
            'date_creation' => now(),
            'commentaire' => 'Ancien commentaire',
        ]);

        $this->put(route('procesverbal.update', $pv), ['commentaire' => 'Nouveau commentaire'])
            ->assertRedirect(route('procesverbal.show', $pv->id));

        $this->assertSame('Nouveau commentaire', $pv->fresh()->commentaire);
    }

    public function test_supprimer_un_pv_ne_supprime_pas_la_consultation(): void
    {
        $consultation = $this->creerConsultation();
        $pv = procesverbal::create([
            'consultation_id' => $consultation->id,
            'date_creation' => now(),
            'commentaire' => 'x',
        ]);

        $this->delete(route('procesverbal.destroy', $pv))
            ->assertRedirect(route('procesverbal.index'));

        $this->assertSame(0, procesverbal::count());
        $this->assertDatabaseHas('consultation', ['id' => $consultation->id]);
    }

    public function test_supprimer_la_consultation_supprime_son_pv(): void
    {
        $consultation = $this->creerConsultation();
        procesverbal::create([
            'consultation_id' => $consultation->id,
            'date_creation' => now(),
            'commentaire' => 'x',
        ]);

        $this->delete(route('consultation.destroy', $consultation));

        $this->assertSame(0, procesverbal::count());
    }

    public function test_la_page_du_pv_affiche_les_fournisseurs_et_leurs_offres(): void
    {
        $consultation = $this->creerConsultation();
        $stylo = $this->creerArticle($consultation, 'Stylo gel', 10);
        $alpha = $this->creerFournisseur('Papeterie Alpha');
        $this->inscrire($consultation, $alpha);
        $this->creerOffre($consultation, $stylo, $alpha, 1.25);
        $pv = procesverbal::create([
            'consultation_id' => $consultation->id,
            'date_creation' => now(),
            'commentaire' => 'ok',
        ]);

        $this->get(route('procesverbal.index'))->assertOk();
        $this->get(route('procesverbal.show', $pv))
            ->assertOk()
            ->assertSee('Stylo gel')
            ->assertSee('Papeterie Alpha');
        $this->get(route('procesverbal.edit', $pv))->assertOk();
        $this->get("/procesverbal/{$pv->id}/pdf")->assertOk();   // page des options du PDF
    }

    public function test_l_export_excel_du_pv_porte_le_nom_de_la_consultation(): void
    {
        Excel::fake();

        $consultation = $this->creerConsultation(['reference' => '12/2026']);

        // Attention : dans cette URL, l'identifiant est celui de la CONSULTATION
        $this->get("/procesverbal/{$consultation->id}/excel")->assertOk();

        Excel::assertDownloaded('Proces_Verbal_12_2026.xlsx', function ($export) {
            return $export instanceof ConsultationExport;
        });
    }

    private function fauxPdf(): object
    {
        $faux = new class {
            public array $options = [];
            public ?string $nom = null;

            public function setOption($cle, $valeur)
            {
                $this->options[$cle] = $valeur;

                return $this;
            }

            public function inline($nom = null)
            {
                $this->nom = $nom;

                return response('PDF');
            }
        };

        SnappyPdf::shouldReceive('loadView')->andReturn($faux);

        return $faux;
    }

    public function test_le_pdf_du_pv_accepte_portrait_et_paysage_seulement(): void
    {
        $consultation = $this->creerConsultation();
        $pv = procesverbal::create([
            'consultation_id' => $consultation->id,
            'date_creation' => now(),
            'commentaire' => 'x',
        ]);

        $faux = $this->fauxPdf();

        $this->post("/procesverbal/{$pv->id}/pdf", ['orientation' => 'landscape'])->assertOk();
        $this->assertSame('Landscape', $faux->options['orientation']);
        $this->assertSame("Proces_Verbal_{$pv->id}.pdf", $faux->nom);

        $faux->options = [];
        $this->post("/procesverbal/{$pv->id}/pdf", ['orientation' => 'portrait'])->assertOk();
        $this->assertSame('Portrait', $faux->options['orientation']);

        // Toute autre valeur est ramenée à "Portrait"
        $faux->options = [];
        $this->post("/procesverbal/{$pv->id}/pdf", ['orientation' => 'n\'importe quoi'])->assertOk();
        $this->assertSame('Portrait', $faux->options['orientation']);
    }
}
