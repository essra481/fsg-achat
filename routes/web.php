<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\consultationController;
use App\Http\Controllers\offreController;
use App\Http\Controllers\fournisseurController;
use App\Http\Controllers\procesverbalController;
use App\Http\Controllers\agentController;
use App\Http\Controllers\documentsController;
use App\Http\Controllers\demandeController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\TwoFactorController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\SignataireController;

/*
|--------------------------------------------------------------------------
| ROUTES PUBLIQUES (seules pages accessibles sans être connecté)
|--------------------------------------------------------------------------
*/

Route::get('/login', [AuthController::class, 'showLogin'])
    ->name('login');

// La limitation des tentatives (par email + IP) est faite dans AuthController.
Route::post('/login', [AuthController::class, 'login']);

// Étape 2 de la connexion (double authentification)
Route::get('/two-factor-challenge', [AuthController::class, 'showChallenge'])
    ->name('two-factor.challenge');

Route::post('/two-factor-challenge', [AuthController::class, 'verifyChallenge'])
    ->name('two-factor.verify');


/*
|--------------------------------------------------------------------------
| ROUTES PROTÉGÉES : il faut être connecté
|--------------------------------------------------------------------------
|
| auth                  : refuse les visiteurs non connectés
| auth.session          : déconnecte les sessions ouvertes avec un ancien
|                         mot de passe (après un changement de mot de passe)
| force.password.change : oblige à changer email/mot de passe à la
|                         première connexion
|
*/

Route::middleware(['auth', 'auth.session', 'force.password.change'])->group(function () {

    Route::post('/logout', [AuthController::class, 'logout'])
        ->name('logout');

    Route::get('/', [DashboardController::class, 'index']);

    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard');


    /*
    |----------------------------------------------------------------------
    | Mon compte
    |----------------------------------------------------------------------
    */

    // Première connexion : configuration obligatoire du compte
    Route::get('/account/setup', [AccountController::class, 'change'])
        ->name('account.change');

    Route::post('/account/setup', [AccountController::class, 'update'])
        ->middleware('throttle:6,1')
        ->name('account.update');

    Route::get('/change-password', function () {
        return redirect()->route('account.profile');
    })->name('password.change');

    Route::get('/account', [AccountController::class, 'profile'])
        ->name('account.profile');

    Route::post('/account/email', [AccountController::class, 'updateEmail'])
        ->middleware('throttle:6,1')
        ->name('account.email.update');

    Route::post('/account/password', [AccountController::class, 'updatePassword'])
        ->middleware('throttle:6,1')
        ->name('account.password.update');

    // Double authentification
    Route::get('/account/two-factor', [TwoFactorController::class, 'show'])
        ->name('two-factor.show');

    Route::post('/account/two-factor/enable', [TwoFactorController::class, 'enable'])
        ->middleware('throttle:6,1')
        ->name('two-factor.enable');

    Route::post('/account/two-factor/confirm', [TwoFactorController::class, 'confirm'])
        ->middleware('throttle:10,1')
        ->name('two-factor.confirm');

    Route::post('/account/two-factor/cancel', [TwoFactorController::class, 'cancel'])
        ->name('two-factor.cancel');

    Route::post('/account/two-factor/recovery-codes', [TwoFactorController::class, 'regenerateRecoveryCodes'])
        ->middleware('throttle:6,1')
        ->name('two-factor.recovery');

    Route::delete('/account/two-factor', [TwoFactorController::class, 'disable'])
        ->middleware('throttle:6,1')
        ->name('two-factor.disable');


    /*
    |----------------------------------------------------------------------
    | Consultations
    |----------------------------------------------------------------------
    */

    Route::post('/consultation/{consultation}/articles', [consultationController::class, 'saveArticles'])
        ->name('consultation.saveArticles');

    Route::resource('consultation', consultationController::class);

    Route::get(
        '/consultation/{consultation}/pdf',
        [consultationController::class, 'pdf']
    )->name('consultation.pdf');

    Route::get(
        '/consultation/{consultation}/print',
        [consultationController::class, 'print']
    )->name('consultation.print');

    Route::get(
        '/consultation/{id}/export-excel',
        [consultationController::class, 'exportExcel']
    )->name('consultation.exportExcel');


    /*
    |----------------------------------------------------------------------
    | Documents / répartition
    |----------------------------------------------------------------------
    */

    Route::get('/documents/{id}/excel', [documentsController::class, 'exportRepartition'])
        ->name('documents.excel');

    Route::get(
        '/documents/repartition/{id}',
        [documentsController::class, 'repartition']
    )->name('documents.repartition');

    Route::get(
        '/documents/repartition/{id}/pdf',
        [documentsController::class, 'pdfForm']
    )->name('documents.pdf.form');

    Route::post(
        '/documents/repartition/{id}/pdf',
        [documentsController::class, 'generatePdf']
    )->name('documents.pdf');


    /*
    |----------------------------------------------------------------------
    | Procès-verbaux
    |----------------------------------------------------------------------
    */

    Route::get('/procesverbal/{id}/pdf', [procesverbalController::class, 'pdfForm'])
        ->name('procesverbal.pdf.form');

    Route::post('/procesverbal/{id}/pdf', [procesverbalController::class, 'generatePdf'])
        ->name('procesverbal.pdf');

    Route::get('/consultations/{id}/excel',
        [consultationController::class, 'excel']
    )->name('consultation.excel');

    Route::get('/procesverbal/{id}/excel', [procesverbalController::class, 'excel'])
        ->name('procesverbal.excel');


    /*
    |----------------------------------------------------------------------
    | IMPORT EXCEL
    |----------------------------------------------------------------------
    */

    // 1. Page d'import
    Route::get('/imports/excel', [ImportController::class, 'create'])
        ->name('imports.create');

    // 2. Après choix du fichier : récupérer les feuilles Excel
    Route::post('/imports/excel/sheets', [ImportController::class, 'sheets'])
        ->name('imports.sheets');

    Route::get('/imports/excel/sheets', function () {
        return redirect()->route('imports.chooseSheet');
    });

    // 3. Page de choix de la feuille
    Route::get('/imports/excel/choose-sheet', [ImportController::class, 'chooseSheet'])
        ->name('imports.chooseSheet');

    // 4. Analyser la feuille choisie
    Route::post('/imports/excel/preview', [ImportController::class, 'preview'])
        ->name('imports.preview');

    // 5. Confirmer l'import
    Route::post('/imports/excel/confirm', [ImportController::class, 'confirm'])
        ->name('imports.confirm');


    /*
    |----------------------------------------------------------------------
    | OFFRES
    |----------------------------------------------------------------------
    */

    Route::resource('offre', offreController::class);

    // Sélection des fournisseurs
    Route::get(
        '/offre/{consultation}/fournisseurs',
        [offreController::class, 'selectFournisseurs']
    )->name('offre.fournisseurs.select');

    Route::post(
        '/offre/{consultation}/fournisseurs',
        [offreController::class, 'addFournisseurs']
    )->name('offre.fournisseurs.add');

    // Ajouter les offres fournisseur par fournisseur
    Route::get(
        '/offre/{consultation}/fournisseur/{fournisseur}',
        [offreController::class, 'createForFournisseur']
    )->name('offre.fournisseur.create');

    Route::post(
        '/offre/{consultation}/fournisseur/{fournisseur}',
        [offreController::class, 'storeForFournisseur']
    )->name('offre.fournisseur.store');

    // Affichage final de toutes les offres
    Route::get(
        '/offre/{consultation}/show',
        [offreController::class, 'show']
    )->name('offre.show');

    // Suppression d'une offre
    Route::delete(
        '/offre/{consultation}/fournisseur/{fournisseur}',
        [offreController::class, 'removeFournisseur']
    )->name('offre.fournisseur.remove');


    /*
    |----------------------------------------------------------------------
    | Gestion des signataires
    |----------------------------------------------------------------------
    */

    Route::resource('signataires', SignataireController::class)
        ->except(['show']);

    Route::delete(
        '/signataires/{signataire}/force-delete',
        [SignataireController::class, 'forceDelete']
    )->name('signataires.forceDelete');


    /*
    |----------------------------------------------------------------------
    | Ressources (inchangées)
    |----------------------------------------------------------------------
    */

    Route::resource('consultations', consultationController::class);
    Route::resource('offre', offreController::class);
    Route::resource('fournisseurs', fournisseurController::class);
    Route::resource('procesverbal', procesverbalController::class);
    Route::resource('agents', agentController::class);
    Route::resource('document', documentsController::class);
    Route::resource('demande', demandeController::class);
});
