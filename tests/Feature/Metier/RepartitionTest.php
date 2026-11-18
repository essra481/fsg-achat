<?php

namespace Tests\Feature\Metier;

use App\Exports\ConsultationExport;
use Barryvdh\Snappy\Facades\SnappyPdf;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Règle métier centrale : la RÉPARTITION.
 *
 * Pour chaque article, on retient le fournisseur dont le prix unitaire HT
 * est le plus bas. Les offres "ND" / "NC" (sans prix) et les prix à 0 sont
 * ignorés. En cas d'égalité, les fournisseurs à égalité sont tous listés et
 * l'égalité est signalée.
 */
class RepartitionTest extends MetierTestCase
{
    /** Ouvre la page de répartition et renvoie [répartition, égalités]. */
    private function repartition($consultation): array
    {
        $reponse = $this->get("/documents/repartition/{$consultation->id}")->assertOk();

        return [
            $reponse->viewData('repartition'),
            $reponse->viewData('egalites'),
        ];
    }

    /** Raccourci : nom du fournisseur => total, trié par nom. */
    private function totauxParFournisseur(array $repartition): array
    {
        $totaux = [];

        foreach ($repartition as $ligne) {
            $totaux[$ligne['fournisseur']->name] = round((float) $ligne['total'], 3);
        }

        ksort($totaux);

        return $totaux;
    }

    public function test_chaque_article_va_au_fournisseur_le_moins_cher(): void
    {
        $consultation = $this->creerConsultation();
        $stylo = $this->creerArticle($consultation, 'Stylo', 100);
        $cahier = $this->creerArticle($consultation, 'Cahier', 50);
        $alpha = $this->creerFournisseur('Alpha');
        $beta = $this->creerFournisseur('Beta');
        $gamma = $this->creerFournisseur('Gamma');
        $this->inscrire($consultation, $alpha, $beta, $gamma);

        // Stylo : Beta est le moins cher (1.0)
        $this->creerOffre($consultation, $stylo, $alpha, 1.2);
        $this->creerOffre($consultation, $stylo, $beta, 1.0);
        $this->creerOffre($consultation, $stylo, $gamma, 1.5);
        // Cahier : Gamma est le moins cher (1.8)
        $this->creerOffre($consultation, $cahier, $alpha, 2.0);
        $this->creerOffre($consultation, $cahier, $beta, 2.5);
        $this->creerOffre($consultation, $cahier, $gamma, 1.8);

        [$repartition, $egalites] = $this->repartition($consultation);

        $this->assertSame(['Beta' => 100.0, 'Gamma' => 90.0], $this->totauxParFournisseur($repartition));
        $this->assertSame([], $egalites);

        // Détail de la ligne de Beta
        $ligneBeta = collect($repartition)->first(fn ($l) => $l['fournisseur']->name === 'Beta');
        $this->assertCount(1, $ligneBeta['articles']);
        $this->assertSame('Stylo', $ligneBeta['articles'][0]['designation']);
        $this->assertEqualsWithDelta(1.0, (float) $ligneBeta['articles'][0]['prix'], 0.0001);
        $this->assertEqualsWithDelta(100.0, (float) $ligneBeta['articles'][0]['total'], 0.0001);
    }

    public function test_le_total_d_un_fournisseur_cumule_tous_les_articles_qu_il_remporte(): void
    {
        $consultation = $this->creerConsultation();
        $stylo = $this->creerArticle($consultation, 'Stylo', 100);
        $cahier = $this->creerArticle($consultation, 'Cahier', 3);
        $alpha = $this->creerFournisseur('Alpha');
        $beta = $this->creerFournisseur('Beta');
        $this->inscrire($consultation, $alpha, $beta);

        $this->creerOffre($consultation, $stylo, $alpha, 1.0);
        $this->creerOffre($consultation, $stylo, $beta, 1.1);
        $this->creerOffre($consultation, $cahier, $alpha, 1.5);   // 3 x 1.5 = 4.5
        $this->creerOffre($consultation, $cahier, $beta, 2.0);

        [$repartition] = $this->repartition($consultation);

        $this->assertSame(['Alpha' => 104.5], $this->totauxParFournisseur($repartition));
        $this->assertCount(2, $repartition[0]['articles']);
    }

    public function test_en_cas_d_egalite_les_fournisseurs_sont_tous_listes_et_l_egalite_est_signalee(): void
    {
        $consultation = $this->creerConsultation();
        $stylo = $this->creerArticle($consultation, 'Stylo', 10);
        $alpha = $this->creerFournisseur('Alpha');
        $beta = $this->creerFournisseur('Beta');
        $gamma = $this->creerFournisseur('Gamma');
        $this->inscrire($consultation, $alpha, $beta, $gamma);

        $this->creerOffre($consultation, $stylo, $alpha, 2.0);
        $this->creerOffre($consultation, $stylo, $beta, 2.0);
        $this->creerOffre($consultation, $stylo, $gamma, 3.0);

        [$repartition, $egalites] = $this->repartition($consultation);

        // Alpha et Beta ont chacun l'article ; Gamma (plus cher) n'a rien
        $this->assertSame(['Alpha' => 20.0, 'Beta' => 20.0], $this->totauxParFournisseur($repartition));

        $this->assertCount(1, $egalites);
        $this->assertSame('Stylo', $egalites[0]['article']);
        $this->assertSame('Alpha et Beta', $egalites[0]['fournisseurs']);
        $this->assertEqualsWithDelta(2.0, (float) $egalites[0]['prix'], 0.0001);
    }

    public function test_les_offres_sans_prix_ou_a_zero_sont_ignorees(): void
    {
        $consultation = $this->creerConsultation();
        $stylo = $this->creerArticle($consultation, 'Stylo', 10);
        $alpha = $this->creerFournisseur('Alpha');
        $beta = $this->creerFournisseur('Beta');
        $gamma = $this->creerFournisseur('Gamma');
        $this->inscrire($consultation, $alpha, $beta, $gamma);

        $this->creerOffre($consultation, $stylo, $alpha, null, 'ND');   // non disponible
        $this->creerOffre($consultation, $stylo, $beta, 0.0);           // prix à zéro
        $this->creerOffre($consultation, $stylo, $gamma, 2.0);          // seule offre valide

        [$repartition, $egalites] = $this->repartition($consultation);

        $this->assertSame(['Gamma' => 20.0], $this->totauxParFournisseur($repartition));
        $this->assertSame([], $egalites);
    }

    public function test_un_article_sans_aucune_offre_valide_n_est_attribue_a_personne(): void
    {
        $consultation = $this->creerConsultation();
        $stylo = $this->creerArticle($consultation, 'Stylo', 10);
        $cahier = $this->creerArticle($consultation, 'Cahier', 5);
        $alpha = $this->creerFournisseur('Alpha');
        $this->inscrire($consultation, $alpha);

        $this->creerOffre($consultation, $stylo, $alpha, 1.0);
        $this->creerOffre($consultation, $cahier, $alpha, null, 'NC');

        [$repartition] = $this->repartition($consultation);

        $this->assertSame(['Alpha' => 10.0], $this->totauxParFournisseur($repartition));

        $designations = collect($repartition[0]['articles'])->pluck('designation')->all();
        $this->assertSame(['Stylo'], $designations);
    }

    public function test_sans_aucune_offre_la_repartition_est_vide_mais_la_page_s_affiche(): void
    {
        $consultation = $this->creerConsultation();
        $this->creerArticle($consultation, 'Stylo', 10);

        [$repartition, $egalites] = $this->repartition($consultation);

        $this->assertSame([], $repartition);
        $this->assertSame([], $egalites);
    }

    public function test_les_offres_d_une_autre_consultation_ne_comptent_pas(): void
    {
        $a = $this->creerConsultation(['reference' => '1/2026'], $this->creerDemande(['reference' => '1/2026']));
        $b = $this->creerConsultation(['reference' => '2/2026'], $this->creerDemande(['reference' => '2/2026']));
        $articleA = $this->creerArticle($a, 'Stylo', 10);
        $articleB = $this->creerArticle($b, 'Stylo', 10);
        $alpha = $this->creerFournisseur('Alpha');
        $beta = $this->creerFournisseur('Beta');
        $this->inscrire($a, $alpha);
        $this->inscrire($b, $beta);

        $this->creerOffre($a, $articleA, $alpha, 5.0);
        $this->creerOffre($b, $articleB, $beta, 1.0);   // moins cher, mais dans l'autre consultation

        [$repartition] = $this->repartition($a);

        $this->assertSame(['Alpha' => 50.0], $this->totauxParFournisseur($repartition));
    }

    // ------------------------------------------------------------------
    // Export Excel de la répartition d'un fournisseur
    // ------------------------------------------------------------------

    private function consultationAvecUnGagnant(string $nomFournisseur = 'Alpha'): array
    {
        $consultation = $this->creerConsultation(['reference' => '12/2026']);
        $stylo = $this->creerArticle($consultation, 'Stylo', 10);
        $fournisseur = $this->creerFournisseur($nomFournisseur);
        $perdant = $this->creerFournisseur('Perdant');
        $this->inscrire($consultation, $fournisseur, $perdant);
        $this->creerOffre($consultation, $stylo, $fournisseur, 1.0);
        $this->creerOffre($consultation, $stylo, $perdant, 9.0);

        return [$consultation, $fournisseur, $perdant];
    }

    public function test_l_export_excel_exige_un_fournisseur(): void
    {
        [$consultation] = $this->consultationAvecUnGagnant();

        $this->get("/documents/{$consultation->id}/excel")->assertStatus(400);
    }

    public function test_l_export_excel_est_refuse_pour_un_fournisseur_sans_article(): void
    {
        [$consultation, , $perdant] = $this->consultationAvecUnGagnant();

        $this->get("/documents/{$consultation->id}/excel?fournisseur_id={$perdant->id}")->assertNotFound();
    }

    public function test_l_export_excel_porte_le_nom_de_la_consultation_et_du_fournisseur(): void
    {
        Excel::fake();

        [$consultation, $fournisseur] = $this->consultationAvecUnGagnant('STE Alpha/Tunis');

        $this->get("/documents/{$consultation->id}/excel?fournisseur_id={$fournisseur->id}")->assertOk();

        // Les "/" de la référence et du nom sont remplacés pour obtenir un nom de fichier valide
        Excel::assertDownloaded('Repartition_12_2026_STE Alpha_Tunis.xlsx', function ($export) {
            return $export instanceof ConsultationExport;
        });
    }

    // ------------------------------------------------------------------
    // PDF (wkhtmltopdf est remplacé par un faux pour le test)
    // ------------------------------------------------------------------

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

            public function stream($nom = null)
            {
                $this->nom = $nom;

                return response('PDF');
            }
        };

        SnappyPdf::shouldReceive('loadView')->andReturn($faux);

        return $faux;
    }

    public function test_le_pdf_de_repartition_respecte_l_orientation_demandee(): void
    {
        $consultation = $this->creerConsultation();
        $faux = $this->fauxPdf();

        $this->post("/documents/repartition/{$consultation->id}/pdf", ['orientation' => 'Landscape'])->assertOk();

        $this->assertSame('Landscape', $faux->options['orientation']);
        $this->assertSame("Repartition_{$consultation->id}.pdf", $faux->nom);
    }

    public function test_le_pdf_de_repartition_ignore_une_orientation_inventee(): void
    {
        $consultation = $this->creerConsultation();
        $faux = $this->fauxPdf();

        $this->post("/documents/repartition/{$consultation->id}/pdf", ['orientation' => 'portrait; rm -rf /'])->assertOk();

        // Valeur par défaut sûre : aucune valeur libre n'est transmise à wkhtmltopdf
        $this->assertSame('portrait', $faux->options['orientation']);
    }
}
