<?php

namespace Tests\Feature\Metier;

use App\Models\Article;
use App\Models\consultation;
use App\Models\demande;
use App\Models\fournisseurs;
use App\Models\offre;
use App\Models\user;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Tests\Support\AlignsSchemaWithApplication;
use Tests\TestCase;

/**
 * Base commune des tests "métier" (le fonctionnement des achats).
 *
 * - Base SQLite en mémoire, recréée pour chaque test : ta vraie base MySQL
 *   n'est jamais touchée.
 * - Un utilisateur est déjà connecté (les pages sont protégées).
 * - Des méthodes "creer..." fabriquent rapidement des données d'exemple.
 */
abstract class MetierTestCase extends TestCase
{
    use AlignsSchemaWithApplication;

    protected user $utilisateur;

    protected function setUp(): void
    {
        parent::setUp();

        Artisan::call('migrate', ['--force' => true]);

        $this->alignerSchemaAvecApplication();

        $this->utilisateur = user::create([
            'name' => 'Agent Test',
            'email' => 'agent@example.com',
            'password' => Hash::make('Mot-de-passe-Test-123!'),
            'must_change_password' => false,
        ]);

        $this->actingAs($this->utilisateur);
    }

    // ------------------------------------------------------------------
    // Fabriques de données d'exemple
    // ------------------------------------------------------------------

    protected function creerDemande(array $attributs = []): demande
    {
        return demande::create(array_merge([
            'reference' => '12/2026',
            'objet' => 'Achat de fournitures de bureau',
            'date_demande' => '2026-09-01',
            'service_demandeur' => 'Informatique',
        ], $attributs));
    }

    protected function creerConsultation(array $attributs = [], ?demande $demande = null): consultation
    {
        $demande ??= $this->creerDemande([
            'reference' => $attributs['reference'] ?? '12/2026',
        ]);

        return consultation::create(array_merge([
            'demande_achat_id' => $demande->id,
            'reference' => $demande->reference,
            'objet' => $demande->objet,
            'date_lancement' => '2026-09-01',
            'date_limite' => '2026-09-09',
        ], $attributs));
    }

    protected function creerArticle(consultation $consultation, string $designation, int $quantite = 1): Article
    {
        return Article::create([
            'consultation_id' => $consultation->id,
            'designation' => $designation,
            'quantite' => $quantite,
        ]);
    }

    protected function creerFournisseur(string $nom, array $attributs = []): fournisseurs
    {
        return fournisseurs::create(array_merge(['name' => $nom], $attributs));
    }

    /** Inscrit des fournisseurs à une consultation (étape "sélection"). */
    protected function inscrire(consultation $consultation, fournisseurs ...$fournisseurs): void
    {
        foreach ($fournisseurs as $fournisseur) {
            $consultation->fournisseurs()->syncWithoutDetaching([$fournisseur->id]);
        }
    }

    protected function creerOffre(
        consultation $consultation,
        Article $article,
        fournisseurs $fournisseur,
        ?float $prix,
        ?string $statut = null
    ): offre {
        return offre::create([
            'consultation_id' => $consultation->id,
            'article_id' => $article->id,
            'fournisseur_id' => $fournisseur->id,
            'prix_unitaire_ht' => $prix,
            'statut_prix' => $statut,
        ]);
    }
}
