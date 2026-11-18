<?php

namespace App\Http\Controllers;

use App\Services\ProcurementImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Throwable;

class ImportController extends Controller
{
    public function __construct(
        private ProcurementImportService $imports
    ) {}


    /*
    |--------------------------------------------------------------------------
    | PAGE D'IMPORT
    |--------------------------------------------------------------------------
    */

    public function create()
    {
        return view('imports.create', [
            'types' => ProcurementImportService::TYPES
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | CHOISIR LE FICHIER
    |--------------------------------------------------------------------------
    |
    | Cette méthode reçoit le fichier Excel et récupère uniquement
    | les noms de ses feuilles.
    |
    */

    public function sheets(Request $request)
    {
        $validated = $request->validate([
            'file' => [
                'required',
                'file',
                'mimes:xlsx,xls',
                'max:20480',
            ],

            'import_type' => [
                'required',
                'in:consultation,pv,repartition',
            ],

            'service_demandeur' => [
                'required',
                'string',
                'max:255',
            ],
        ], [
            'file.required' =>
                'Veuillez sélectionner un fichier Excel.',

            'file.mimes' =>
                'Le fichier doit être au format .xlsx ou .xls.',

            'file.max' =>
                'La taille maximale autorisée est de 20 Mo.',

            'import_type.required' =>
                'Veuillez sélectionner le type d’import.',

            'service_demandeur.required' =>
                'Le service demandeur est obligatoire.',
        ]);


        /*
        |--------------------------------------------------------------------------
        | STOCKER LE FICHIER TEMPORAIREMENT
        |--------------------------------------------------------------------------
        */

        $path = $request->file('file')->store('imports');


        try {

            /*
            |--------------------------------------------------------------------------
            | RÉCUPÉRER LES FEUILLES
            |--------------------------------------------------------------------------
            */

            $sheets = $this->imports->getExcelSheets(
                Storage::path($path)
            );


            if (empty($sheets)) {

                Storage::delete($path);

                return back()
                    ->withInput()
                    ->with(
                        'error',
                        'Aucune feuille Excel trouvée dans ce fichier.'
                    );
            }


            /*
            |--------------------------------------------------------------------------
            | SAUVEGARDER LE FICHIER EN SESSION
            |--------------------------------------------------------------------------
            */

            session([
                'procurement_import_file' => [
                    'path' => $path,

                    'type' => $validated['import_type'],

                    'service' => $validated['service_demandeur'],

                    'sheets' => $sheets,
                ],
            ]);


            /*
            |--------------------------------------------------------------------------
            | REDIRECTION VERS LA PAGE DE CHOIX DE FEUILLE
            |--------------------------------------------------------------------------
            |
            | IMPORTANT :
            | On ne retourne plus directement la vue ici.
            |
            | Avant :
            | return view('imports.choose-sheet', compact('sheets'));
            |
            | Cela laissait l'URL :
            | /imports/excel/sheets
            |
            | Si l'utilisateur actualisait la page, le navigateur faisait
            | GET /imports/excel/sheets alors que cette route accepte POST.
            |
            | Maintenant on redirige vers une vraie route GET.
            |
            */

            return redirect()->route('imports.chooseSheet');


        } catch (Throwable $e) {

            Storage::delete($path);

            Log::error('Erreur lors de la lecture du fichier Excel.', [
                'message' => $e->getMessage(),
                'exception' => get_class($e),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()
                ->withInput()
                ->with(
                    'error',
                    'Impossible de lire le fichier Excel. Vérifiez que le fichier est valide puis réessayez.'
                );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | PAGE DE CHOIX DE LA FEUILLE
    |--------------------------------------------------------------------------
    |
    | Cette page est accessible en GET.
    |
    | URL :
    | /imports/excel/choose-sheet
    |
    */

    public function chooseSheet()
    {
        /*
        |--------------------------------------------------------------------------
        | RÉCUPÉRER L'ÉTAT DE L'IMPORT
        |--------------------------------------------------------------------------
        */

        $state = session('procurement_import_file');


        /*
        |--------------------------------------------------------------------------
        | VÉRIFIER LA SESSION
        |--------------------------------------------------------------------------
        */

       if (!$state) {
    dd([
        'erreur' => 'Session procurement_import introuvable',
        'session_id' => session()->getId(),
        'toutes_les_sessions' => session()->all(),
    ]);
}

if (!isset($state['path'])) {
    dd([
        'erreur' => 'Clé path absente',
        'procurement_import' => $state,
    ]);
}


        /*
        |--------------------------------------------------------------------------
        | VÉRIFIER LES FEUILLES
        |--------------------------------------------------------------------------
        */

        if (
            !isset($state['sheets']) ||
            empty($state['sheets'])
        ) {

            return redirect()
                ->route('imports.create')
                ->with(
                    'error',
                    'Aucune feuille Excel disponible. Veuillez recommencer.'
                );
        }


        /*
        |--------------------------------------------------------------------------
        | AFFICHER LA PAGE
        |--------------------------------------------------------------------------
        |
        | Le fichier réel est :
        | resources/views/imports/choose-sheet.blade.php
        |
        | Donc :
        | view('imports.choose-sheet')
        |
        */

        return view(
            'imports.choose-sheet',
            [
                'sheets' => $state['sheets']
            ]
        );
    }


    /*
    |--------------------------------------------------------------------------
    | ANALYSER LA FEUILLE CHOISIE
    |--------------------------------------------------------------------------
    */

    public function preview(Request $request)
    {
        $validated = $request->validate([
            'sheet' => [
                'required',
                'string',
            ],
        ], [
            'sheet.required' =>
                'Veuillez choisir une feuille Excel.',
        ]);


        /*
        |--------------------------------------------------------------------------
        | RÉCUPÉRER LES INFORMATIONS DU FICHIER
        |--------------------------------------------------------------------------
        */

        $state = session('procurement_import_file');


        if (!$state) {

            return redirect()
                ->route('imports.create')
                ->with(
                    'error',
                    'La session d’import a expiré. Veuillez recommencer.'
                );
        }


        /*
        |--------------------------------------------------------------------------
        | VÉRIFIER QUE LE FICHIER EXISTE ENCORE
        |--------------------------------------------------------------------------
        */

        if (
            !isset($state['path']) ||
            !Storage::exists($state['path'])
        ) {

            session()->forget('procurement_import_file');

            return redirect()
                ->route('imports.create')
                ->with(
                    'error',
                    'Le fichier temporaire n’existe plus. Veuillez recommencer.'
                );
        }


        /*
        |--------------------------------------------------------------------------
        | VÉRIFIER QUE LA FEUILLE EXISTE
        |--------------------------------------------------------------------------
        */

        if (
            !isset($state['sheets']) ||
            !in_array($validated['sheet'], $state['sheets'], true)
        ) {

            return back()
                ->withInput()
                ->with(
                    'error',
                    'La feuille sélectionnée est invalide.'
                );
        }


        try {

            /*
            |--------------------------------------------------------------------------
            | ANALYSER UNIQUEMENT LA FEUILLE CHOISIE
            |--------------------------------------------------------------------------
            */

            $summary = $this->imports->preview(
                Storage::path($state['path']),
                $state['type'],
                $state['service'],
                $validated['sheet']
            );


            /*
            |--------------------------------------------------------------------------
            | SAUVEGARDER L'ÉTAT DE L'IMPORT
            |--------------------------------------------------------------------------
            */

            session([
                'procurement_import' => [
                    'path' => $state['path'],

                    'type' => $state['type'],

                    'service' => $state['service'],

                    'sheet' => $validated['sheet'],

                    'summary' => $summary,
                ],
            ]);


            /*
            |--------------------------------------------------------------------------
            | AFFICHER L'APERÇU
            |--------------------------------------------------------------------------
            */

            return view(
                'imports.preview',
                compact('summary')
            );


        } catch (Throwable $e) {

            // Messages métier rédigés pour l'utilisateur par le service
            // d'import (colonne manquante, consultation introuvable...) :
            // ce sont des RuntimeException "exactes". Attention : les erreurs
            // SQL (PDOException) héritent aussi de RuntimeException en PHP,
            // c'est pourquoi on compare la classe exacte.
            if (get_class($e) === \RuntimeException::class) {

                return back()->withErrors([
                    'file' => $e->getMessage()
                ]);
            }

            // Sécurité : on ne renvoie jamais un message technique brut
            // (chemins du serveur, noms de classes, SQL...).
            Log::error('Erreur lors de l’analyse du fichier Excel.', [
                'message' => $e->getMessage(),
                'exception' => get_class($e),
            ]);

            return back()->withErrors([
                'file' => 'Impossible d’analyser cette feuille. Vérifiez le format du fichier puis réessayez.'
            ]);
        }
    }


    /*
    |--------------------------------------------------------------------------
    | CONFIRMER L'IMPORT
    |--------------------------------------------------------------------------
    */

    public function confirm()
    {
        /*
        |--------------------------------------------------------------------------
        | RÉCUPÉRER LA SESSION
        |--------------------------------------------------------------------------
        */

        $state = session('procurement_import');


        if (
            !$state ||
            !isset($state['path'])
        ) {

            return redirect()
                ->route('imports.create')
                ->with(
                    'error',
                    'La session d’import a expiré. Veuillez recommencer.'
                );
        }


        /*
        |--------------------------------------------------------------------------
        | VÉRIFIER LE FICHIER
        |--------------------------------------------------------------------------
        */

        if (!Storage::exists($state['path'])) {

            session()->forget('procurement_import');

            return redirect()
                ->route('imports.create')
                ->with(
                    'error',
                    'Le fichier temporaire n’existe plus. Veuillez recommencer.'
                );
        }


        /*
        |--------------------------------------------------------------------------
        | VÉRIFIER LA FEUILLE
        |--------------------------------------------------------------------------
        */

        if (
            !isset($state['sheet']) ||
            $state['sheet'] === ''
        ) {

            return redirect()
                ->route('imports.create')
                ->with(
                    'error',
                    'Aucune feuille Excel n’a été sélectionnée.'
                );
        }


        try {

            /*
            |--------------------------------------------------------------------------
            | RÉANALYSER LA MÊME FEUILLE
            |--------------------------------------------------------------------------
            |
            | On relit exactement la feuille choisie par l'utilisateur.
            |
            */

            $summary = $this->imports->preview(
                Storage::path($state['path']),
                $state['type'],
                $state['service'],
                $state['sheet']
            );


            /*
            |--------------------------------------------------------------------------
            | EFFECTUER L'IMPORT
            |--------------------------------------------------------------------------
            */

            $count = $this->imports->import(
                $summary,
                $state['service']
            );


        }  catch (Throwable $e) {

    Log::error('Erreur lors de l’import Excel.', [
        'message' => $e->getMessage(),
        'exception' => get_class($e),
        'type' => $state['type'] ?? null,
        'sheet' => $state['sheet'] ?? null,
        'trace' => $e->getTraceAsString(),
    ]);

    return redirect()
        ->route('imports.create')
        ->with(
            'error',
            'Import impossible : ' . $e->getMessage()
        );



        } finally {

            /*
            |--------------------------------------------------------------------------
            | SUPPRIMER LE FICHIER TEMPORAIRE
            |--------------------------------------------------------------------------
            */

            if (
                isset($state['path']) &&
                Storage::exists($state['path'])
            ) {
                Storage::delete($state['path']);
            }


            /*
            |--------------------------------------------------------------------------
            | NETTOYER LES SESSIONS
            |--------------------------------------------------------------------------
            */

            session()->forget('procurement_import');

            session()->forget('procurement_import_file');
        }


        /*
        |--------------------------------------------------------------------------
        | REDIRECTION APRÈS IMPORT
        |--------------------------------------------------------------------------
        */

        return redirect()
            ->route('consultation.index')
            ->with(
                'success',
                "Import {$state['type']} terminé : "
                . "{$count} élément(s) enregistré(s)."
            );
    }
}