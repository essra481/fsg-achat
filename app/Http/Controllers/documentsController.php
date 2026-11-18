<?php

namespace App\Http\Controllers;

use App\Models\documents;
use App\Models\consultation;
use App\Models\Signataire;
use App\Exports\ConsultationExport;
use Barryvdh\Snappy\Facades\SnappyPdf as Pdf;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;

class documentsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
   public function index()
{
    $consultations = consultation::latest()->get();

    return view('documents.index', compact('consultations'));
}

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('documents.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'type_document' => 'required|string|max:255',
            'chemin_fichier' => 'required|string|max:255',
            'date_ajout' => 'required|date',
            'fk_consultation_id' => 'nullable|integer',
            'fournisseur_id' => 'nullable|integer',
            'proces_verbal_id' => 'nullable|integer',
        ]);

        documents::create([
            'type_document' => $request->type_document,
            'chemin_fichier' => $request->chemin_fichier,
            'date_ajout' => $request->date_ajout,
            'fk_consultation_id' => $request->fk_consultation_id,
            'fournisseur_id' => $request->fournisseur_id,
            'proces_verbal_id' => $request->proces_verbal_id
        ]);

        return redirect()
            ->route('documents.index')
            ->with('success', 'Document ajouté avec succès');
    }

    /**
     * Display the specified resource.
     */
    public function show(documents $document)
    {
        return view('documents.show', compact('document'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(documents $document)
    {
        return view('documents.edit', compact('document'));
    }

    /**
     * Update the specified resource.
     */
    public function update(Request $request, documents $document)
    {
        $request->validate([
            'type_document' => 'required|string|max:255',
            'chemin_fichier' => 'required|string|max:255',
            'date_ajout' => 'required|date',
            'fk_consultation_id' => 'nullable|integer',
            'fournisseur_id' => 'nullable|integer',
            'proces_verbal_id' => 'nullable|integer',
        ]);

        $document->update([
            'type_document' => $request->type_document,
            'chemin_fichier' => $request->chemin_fichier,
            'date_ajout' => $request->date_ajout,
            'fk_consultation_id' => $request->fk_consultation_id,
            'fournisseur_id' => $request->fournisseur_id,
            'proces_verbal_id' => $request->proces_verbal_id
        ]);

        return redirect()
            ->route('documents.index')
            ->with('success', 'Document modifié avec succès');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(documents $document)
    {
        $document->delete();

        return redirect()
            ->route('documents.index')
            ->with('success', 'Document supprimé');
    }

    /**
     * Construire la répartition des articles.
     *
     * Règles :
     * - comparaison par article
     * - uniquement les offres de cette consultation
     * - NULL / ND ignorés
     * - 0 ignoré
     * - prix minimum recherché
     * - en cas d'égalité, tous les fournisseurs concernés reçoivent l'article
     */
    private function construireRepartition($consultation)
    {
        $repartition = [];
        $egalites = [];

        foreach ($consultation->articles as $article) {

            /*
            |--------------------------------------------------------------------------
            | Récupérer les offres de cet article
            |--------------------------------------------------------------------------
            */
            $offres = $consultation->offres
                ->where('article_id', $article->id);

            /*
            |--------------------------------------------------------------------------
            | Garder uniquement les vrais prix
            |
            | NULL = ND
            | 0 = pas un prix valide
            |--------------------------------------------------------------------------
            */
            $offresValides = $offres->filter(function ($offre) {

                return $offre->prix_unitaire_ht !== null
                    && (float) $offre->prix_unitaire_ht > 0;
            });

            /*
            |--------------------------------------------------------------------------
            | Aucun prix valide pour cet article
            |--------------------------------------------------------------------------
            */
            if ($offresValides->isEmpty()) {
                continue;
            }

            /*
            |--------------------------------------------------------------------------
            | Chercher le prix minimum
            |--------------------------------------------------------------------------
            */
            $prixMinimum = $offresValides->min(function ($offre) {

                return (float) $offre->prix_unitaire_ht;
            });

            /*
            |--------------------------------------------------------------------------
            | Trouver TOUS les fournisseurs qui ont le prix minimum
            |--------------------------------------------------------------------------
            */
            $offresMinimum = $offresValides->filter(function ($offre) use ($prixMinimum) {

                return (float) $offre->prix_unitaire_ht
                    == (float) $prixMinimum;
            });

            /*
            |--------------------------------------------------------------------------
            | Égalité entre plusieurs fournisseurs
            |--------------------------------------------------------------------------
            */
            if ($offresMinimum->count() > 1) {

                $nomsFournisseurs = $offresMinimum
                    ->map(function ($offre) {

                        return $offre->fournisseur
                            ? $offre->fournisseur->name
                            : 'Fournisseur inconnu';
                    })
                    ->unique()
                    ->values()
                    ->implode(' et ');

                $egalites[] = [
                    'article' => $article->designation,
                    'prix' => $prixMinimum,
                    'fournisseurs' => $nomsFournisseurs,
                ];
            }

            /*
            |--------------------------------------------------------------------------
            | Ajouter l'article à chaque fournisseur ayant le prix minimum
            |--------------------------------------------------------------------------
            */
            foreach ($offresMinimum as $offreMinimum) {

                $fournisseurId = $offreMinimum->fournisseur_id;

                /*
                |--------------------------------------------------------------------------
                | Créer le fournisseur dans la répartition s'il n'existe pas
                |--------------------------------------------------------------------------
                */
                if (!isset($repartition[$fournisseurId])) {

                    $repartition[$fournisseurId] = [
                        'fournisseur' => $offreMinimum->fournisseur,
                        'articles' => [],
                        'total' => 0
                    ];
                }

                /*
                |--------------------------------------------------------------------------
                | Calcul du total
                |--------------------------------------------------------------------------
                */
                $quantite = (float) $article->quantite;

                $total = $quantite * (float) $prixMinimum;

                /*
                |--------------------------------------------------------------------------
                | Ligne article
                |--------------------------------------------------------------------------
                */
                $ligne = [
                    'article_id' => $article->id,
                    'designation' => $article->designation,
                    'description' => $article->description ?? '',
                    'quantite' => $article->quantite,
                    'prix' => $prixMinimum,
                    'total' => $total
                ];

                /*
                |--------------------------------------------------------------------------
                | Ajouter l'article au fournisseur
                |--------------------------------------------------------------------------
                */
                $repartition[$fournisseurId]['articles'][] = $ligne;

                /*
                |--------------------------------------------------------------------------
                | Ajouter au total fournisseur
                |--------------------------------------------------------------------------
                */
                $repartition[$fournisseurId]['total'] += $total;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Réindexer les fournisseurs
        |--------------------------------------------------------------------------
        */
        $repartition = array_values($repartition);

        return [
            'repartition' => $repartition,
            'egalites' => $egalites
        ];
    }

    /**
     * Répartition normale
     */
    public function repartition($id)
    {
        $consultation = consultation::with([
            'articles',
            'offres.fournisseur',
            'offres.article'
        ])->findOrFail($id);

        /*
        |--------------------------------------------------------------------------
        | Construire la répartition
        |--------------------------------------------------------------------------
        */
        $resultat = $this->construireRepartition($consultation);

        $repartition = $resultat['repartition'];

        $egalites = $resultat['egalites'];

        /*
        |--------------------------------------------------------------------------
        | Récupérer le doyen actif
        |--------------------------------------------------------------------------
        */
        $doyen = Signataire::where('type', 'العميد')
            ->where('actif', true)
            ->first();

        return view(
            'documents.repartition',
            compact(
                'consultation',
                'repartition',
                'egalites',
                'doyen'
            )
        );
    }

    /**
     * Formulaire des options PDF
     */
    public function pdfForm($id)
    {
        $consultation = consultation::findOrFail($id);

        return view(
            'documents.pdf_options',
            compact('consultation')
        );
    }

    /**
     * Génération du PDF de répartition
     */
   public function generatePdf(Request $request, $id)
{
    // Sécurité : seules ces deux valeurs sont transmises à wkhtmltopdf
    $orientation = in_array(
        $request->orientation,
        ['Portrait', 'Landscape', 'portrait', 'landscape'],
        true
    ) ? $request->orientation : 'portrait';

    $consultation = consultation::with([
        'articles',
        'offres.fournisseur',
        'offres.article'
    ])->findOrFail($id);

    /*
    |--------------------------------------------------------------------------
    | Construire la même répartition que la page normale
    |--------------------------------------------------------------------------
    */
    $resultat = $this->construireRepartition($consultation);

    $repartition = $resultat['repartition'];

    $egalites = $resultat['egalites'];

    /*
    |--------------------------------------------------------------------------
    | Récupérer le doyen actif
    |--------------------------------------------------------------------------
    */
    $doyen = Signataire::where('type', 'العميد')
        ->where('actif', true)
        ->first();

    /*
    |--------------------------------------------------------------------------
    | Générer le PDF
    |--------------------------------------------------------------------------
    */
    $pdf = Pdf::loadView(
        'documents.repartition_pdf',
        compact(
            'consultation',
            'repartition',
            'egalites',
            'doyen'
        )
    );

    $pdf->setOption(
        'orientation',
        $orientation
    );

    return $pdf->inline(
        'Repartition_' . $consultation->id . '.pdf'
    );
}
    /**
     * Export Répartition Excel pour un fournisseur
     */
    public function exportRepartition($id)
    {
        set_time_limit(180);

        $consultation = consultation::with([
            'articles',
            'offres.fournisseur',
            'offres.article'
        ])->findOrFail($id);

        /*
        |--------------------------------------------------------------------------
        | Construire la répartition
        |--------------------------------------------------------------------------
        */
        $resultat = $this->construireRepartition($consultation);

        $repartition = $resultat['repartition'];

        $egalites = $resultat['egalites'];

        /*
        |--------------------------------------------------------------------------
        | Vérifier le fournisseur demandé
        |--------------------------------------------------------------------------
        */
        $fournisseurId = request()->query('fournisseur_id');

        if (!$fournisseurId) {
            abort(400, 'Aucun fournisseur sélectionné.');
        }

        /*
        |--------------------------------------------------------------------------
        | Chercher uniquement ce fournisseur
        |--------------------------------------------------------------------------
        */
        $repartitionFournisseur = collect($repartition)
            ->filter(function ($rep) use ($fournisseurId) {

                return $rep['fournisseur']
                    && (string) $rep['fournisseur']->id
                        === (string) $fournisseurId;
            })
            ->values()
            ->all();

        /*
        |--------------------------------------------------------------------------
        | Vérifier que le fournisseur existe dans la répartition
        |--------------------------------------------------------------------------
        */
        if (empty($repartitionFournisseur)) {
            abort(
                404,
                'Ce fournisseur ne possède aucun article dans la répartition.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Nom du fournisseur
        |--------------------------------------------------------------------------
        */
        $fournisseur =
            $repartitionFournisseur[0]['fournisseur'];

        $nomFournisseur =
            $fournisseur->name
            ?? 'Fournisseur_' . $fournisseur->id;

        /*
        |--------------------------------------------------------------------------
        | Nettoyer le nom du fichier
        |--------------------------------------------------------------------------
        */
        $nomFichier = preg_replace(
            '/[\\\\\/:*?"<>|]/',
            '_',
            $nomFournisseur
        );

        $nomFichier = trim($nomFichier);

        if ($nomFichier === '') {
            $nomFichier =
                'Fournisseur_' . $fournisseur->id;
        }

        /*
        |--------------------------------------------------------------------------
        | Référence consultation
        |--------------------------------------------------------------------------
        */
        $reference = str_replace(
            ['/', '\\'],
            '_',
            $consultation->reference
        );

        $filename =
            'Repartition_' .
            $reference .
            '_' .
            $nomFichier .
            '.xlsx';

        /*
        |--------------------------------------------------------------------------
        | Export Excel
        |--------------------------------------------------------------------------
        */
        return Excel::download(
            new ConsultationExport(
                $consultation,
                'repartition',
                $repartitionFournisseur,
                $egalites
            ),
            $filename
        );
    }
}


