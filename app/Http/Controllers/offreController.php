<?php

namespace App\Http\Controllers;

use App\Models\consultation;
use App\Models\offre;
use App\Models\fournisseurs;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class offreController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | 1. Liste des consultations
    |--------------------------------------------------------------------------
    */

   public function index()
{
    $consultation = consultation::with('fournisseurs')
        ->latest()
        ->get();

    return view(
        'offre.index',
        compact('consultation')
    );
}

    /*
    |--------------------------------------------------------------------------
    | 2. Gestion des fournisseurs d'une consultation
    |--------------------------------------------------------------------------
    */

    public function edit($id)
    {
        $consultation = consultation::with([
            'articles',
            'fournisseurs',
            'offres'
        ])->findOrFail($id);

        return view(
            'offre.edit',
            compact('consultation')
        );
    }

    /*
    |--------------------------------------------------------------------------
    | 3. Sélection des fournisseurs
    |--------------------------------------------------------------------------
    */

    public function selectFournisseurs($id)
    {
        $consultation = consultation::findOrFail($id);

        $fournisseurs = fournisseurs::all();

        return view(
            'offre.fournisseurs',
            compact(
                'consultation',
                'fournisseurs'
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | 4. Ajouter les fournisseurs à la consultation
    |--------------------------------------------------------------------------
    */

    public function addFournisseurs(
        Request $request,
        $id
    ) {
        $consultation = consultation::findOrFail($id);

        $request->validate([
            'fournisseurs' => [
                'required',
                'array'
            ],

            'fournisseurs.*' => [
                'exists:fournisseurs,id'
            ],

        ], [

            'fournisseurs.required' =>
                'Veuillez sélectionner au moins un fournisseur.',

        ]);

        foreach ($request->fournisseurs as $fournisseurId) {

            $consultation
                ->fournisseurs()
                ->syncWithoutDetaching([
                    $fournisseurId
                ]);
        }

        return redirect()
            ->route(
                'offre.edit',
                $id
            )
            ->with(
                'success',
                'Fournisseurs ajoutés avec succès.'
            );
    }

    /*
    |--------------------------------------------------------------------------
    | 5. Saisir les offres d'un seul fournisseur
    |--------------------------------------------------------------------------
    */

    public function createForFournisseur(
        $consultationId,
        $fournisseurId
    ) {
        $consultation = consultation::with(
            'articles'
        )->findOrFail($consultationId);

        $fournisseur = fournisseurs::findOrFail(
            $fournisseurId
        );

        /*
        |--------------------------------------------------------------------------
        | Vérifier que le fournisseur appartient à la consultation
        |--------------------------------------------------------------------------
        */

        $appartient = $consultation
            ->fournisseurs()
            ->where(
                'fournisseurs.id',
                $fournisseurId
            )
            ->exists();

        if (!$appartient) {
            abort(403);
        }

        /*
        |--------------------------------------------------------------------------
        | Récupérer les offres existantes
        |--------------------------------------------------------------------------
        */

        $offres = offre::where(
                'consultation_id',
                $consultationId
            )
            ->where(
                'fournisseur_id',
                $fournisseurId
            )
            ->get()
            ->keyBy('article_id');

        return view(
            'offre.fournisseur',
            compact(
                'consultation',
                'fournisseur',
                'offres'
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | 6. Enregistrer les offres d'un seul fournisseur
    |--------------------------------------------------------------------------
    */

    public function storeForFournisseur(
        Request $request,
        $consultationId,
        $fournisseurId
    ) {
        /*
        |--------------------------------------------------------------------------
        | Vérifier la consultation
        |--------------------------------------------------------------------------
        */

        $consultation = consultation::findOrFail(
            $consultationId
        );

        /*
        |--------------------------------------------------------------------------
        | Vérifier le fournisseur
        |--------------------------------------------------------------------------
        */

        $fournisseur = fournisseurs::findOrFail(
            $fournisseurId
        );

        /*
        |--------------------------------------------------------------------------
        | Vérifier que le fournisseur appartient
        | à cette consultation
        |--------------------------------------------------------------------------
        */

        $appartient = $consultation
            ->fournisseurs()
            ->where(
                'fournisseurs.id',
                $fournisseurId
            )
            ->exists();

        if (!$appartient) {
            abort(403);
        }

        /*
        |--------------------------------------------------------------------------
        | Validation
        |--------------------------------------------------------------------------
        */

        $request->validate([
            'article_id' => [
                'required',
                'array'
            ],

            'article_id.*' => [
                'required',
                'exists:article,id'
            ],

            'prix_unitaire_ht' => [
                'required',
                'array'
            ],

            'prix_unitaire_ht.*' => [
                'required',
                'string'
            ],

        ], [

            'article_id.required' =>
                'Les articles sont obligatoires.',

            'prix_unitaire_ht.required' =>
                'Veuillez saisir un prix, ND ou NC.',

            'prix_unitaire_ht.*.required' =>
                'Veuillez saisir un prix, ND ou NC.',

        ]);

        /*
        |--------------------------------------------------------------------------
        | Vérifier le nombre d'articles et de prix
        |--------------------------------------------------------------------------
        */

        if (
            count($request->article_id)
            !==
            count($request->prix_unitaire_ht)
        ) {
            return back()
                ->withInput()
                ->withErrors([
                    'prix_unitaire_ht' =>
                        'Les données des articles et des prix ne correspondent pas.'
                ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Transaction
        |--------------------------------------------------------------------------
        */

        DB::beginTransaction();

        try {

            /*
            |--------------------------------------------------------------------------
            | Parcourir les articles
            |--------------------------------------------------------------------------
            */

            foreach (
                $request->article_id as $index => $articleId
            ) {

                /*
                |--------------------------------------------------------------------------
                | Récupérer la valeur saisie
                |--------------------------------------------------------------------------
                */

                $prix = trim(
                    $request->prix_unitaire_ht[$index] ?? ''
                );

                /*
                |--------------------------------------------------------------------------
                | Par défaut :
                | aucun statut
                |--------------------------------------------------------------------------
                */

                $statutPrix = null;

                /*
                |--------------------------------------------------------------------------
                | ND
                |--------------------------------------------------------------------------
                */

                if (
                    strtoupper($prix) === 'ND'
                ) {

                    $prix = null;

                    $statutPrix = 'ND';
                }

                /*
                |--------------------------------------------------------------------------
                | NC
                |--------------------------------------------------------------------------
                */

                elseif (
                    strtoupper($prix) === 'NC'
                ) {

                    $prix = null;

                    $statutPrix = 'NC';
                }

                /*
                |--------------------------------------------------------------------------
                | Prix numérique
                |--------------------------------------------------------------------------
                */

                else {

                    /*
                    |--------------------------------------------------------------------------
                    | Accepter la virgule
                    |
                    | 10,500 devient 10.500
                    |--------------------------------------------------------------------------
                    */

                    $prix = str_replace(
                        ',',
                        '.',
                        $prix
                    );

                    /*
                    |--------------------------------------------------------------------------
                    | Vérifier que c'est bien un nombre
                    |--------------------------------------------------------------------------
                    */

                    if (
                        !preg_match(
                            '/^[0-9]+(\.[0-9]+)?$/',  
                            $prix
                        )
                    ) {

                        DB::rollBack();

                        return back()
                            ->withInput()
                            ->withErrors([
                                'prix_unitaire_ht' =>
                                    'Le prix doit être un nombre supérieur ou égal à 0, ou ND ou NC.'
                            ]);
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Convertir en nombre
                    |--------------------------------------------------------------------------
                    */

                    $prix = (float) $prix;

                    /*
                    |--------------------------------------------------------------------------
                    | Un prix numérique n'a aucun statut
                    |--------------------------------------------------------------------------
                    */

                    $statutPrix = null;
                }

                /*
                |--------------------------------------------------------------------------
                | Chercher l'offre existante
                |--------------------------------------------------------------------------
                */

                $offre = offre::where(
                        'consultation_id',
                        $consultationId
                    )
                    ->where(
                        'fournisseur_id',
                        $fournisseurId
                    )
                    ->where(
                        'article_id',
                        $articleId
                    )
                    ->first();

                /*
                |--------------------------------------------------------------------------
                | L'offre existe déjà
                |--------------------------------------------------------------------------
                */

                if ($offre) {

                    $offre->prix_unitaire_ht = $prix;

                    $offre->statut_prix = $statutPrix;

                    $offre->save();
                }

                /*
                |--------------------------------------------------------------------------
                | L'offre n'existe pas
                |--------------------------------------------------------------------------
                */

                else {

                    $nouvelleOffre = new offre();

                    $nouvelleOffre->consultation_id =
                        $consultationId;

                    $nouvelleOffre->fournisseur_id =
                        $fournisseurId;

                    $nouvelleOffre->article_id =
                        $articleId;

                    $nouvelleOffre->prix_unitaire_ht =
                        $prix;

                    $nouvelleOffre->statut_prix =
                        $statutPrix;

                    $nouvelleOffre->save();
                }
            }

            /*
            |--------------------------------------------------------------------------
            | Valider la transaction
            |--------------------------------------------------------------------------
            */

            DB::commit();

            return redirect()
                ->route(
                    'offre.edit',
                    $consultationId
                )
                ->with(
                    'success',
                    'Les offres de ' .
                    $fournisseur->name .
                    ' ont été enregistrées avec succès.'
                );

        } catch (\Exception $e) {

            /*
            |--------------------------------------------------------------------------
            | Annuler en cas d'erreur
            |--------------------------------------------------------------------------
            */

            DB::rollBack();

            return back()
                ->withInput()
                ->withErrors([
                    'prix_unitaire_ht' =>
                        'Une erreur est survenue lors de l’enregistrement des offres.'
                ]);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | 7. Afficher toutes les offres ensemble
    |--------------------------------------------------------------------------
    */

    public function show($id)
    {
        $consultation = consultation::with([
            'articles',
            'fournisseurs',
            'offres.fournisseur',
            'offres.article'
        ])->findOrFail($id);

        return view(
            'offre.show',
            compact('consultation')
        );
    }

    /*
    |--------------------------------------------------------------------------
    | 8. Supprimer un fournisseur de la consultation
    |--------------------------------------------------------------------------
    */

    public function removeFournisseur(
        $consultationId,
        $fournisseurId
    ) {
        $consultation = consultation::findOrFail(
            $consultationId
        );

        $fournisseur = fournisseurs::findOrFail(
            $fournisseurId
        );

        /*
        |--------------------------------------------------------------------------
        | Vérifier que le fournisseur appartient
        | à cette consultation
        |--------------------------------------------------------------------------
        */

        $existe = $consultation
            ->fournisseurs()
            ->where(
                'fournisseurs.id',
                $fournisseurId
            )
            ->exists();

        if (!$existe) {

            return redirect()
                ->route(
                    'offre.edit',
                    $consultationId
                )
                ->with(
                    'error',
                    'Ce fournisseur n\'appartient pas à cette consultation.'
                );
        }

        /*
        |--------------------------------------------------------------------------
        | Supprimer les offres de ce fournisseur
        |--------------------------------------------------------------------------
        */

        offre::where(
            'consultation_id',
            $consultationId
        )
        ->where(
            'fournisseur_id',
            $fournisseurId
        )
        ->delete();

        /*
        |--------------------------------------------------------------------------
        | Retirer le fournisseur de la consultation
        |--------------------------------------------------------------------------
        */

        $consultation
            ->fournisseurs()
            ->detach(
                $fournisseurId
            );

        return redirect()
            ->route(
                'offre.edit',
                $consultationId
            )
            ->with(
                'success',
                'Le fournisseur "' .
                $fournisseur->name .
                '" a été retiré de la consultation.'
            );
    }
}
