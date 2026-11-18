<?php

namespace App\Http\Controllers;

use App\Models\demande;
use App\Models\consultation;
use Illuminate\Http\Request;
use Carbon\Carbon;

class demandeController extends Controller
{
    /**
     * Afficher la liste des demandes.
     */
    public function index()
{
    $demande = Demande::latest()->get();

    return view('demande.index', compact('demande'));
}

    /**
     * Formulaire de création.
     */
    public function create()
    {
        return view('demande.create');
    }

    /**
     * Enregistrer une nouvelle demande
     * et créer automatiquement la consultation liée.
     */
    public function store(Request $request)
    {
        $request->validate([
            'reference' => 'required|string|max:255|unique:demande,reference',
            'objet' => 'required|string|max:1000',
            'date_demande' => 'required|date',
            'service_demandeur' => 'required|string|max:255',
        ]);

        /*
        |--------------------------------------------------------------------------
        | 1. Création de la demande
        |--------------------------------------------------------------------------
        */

        $demande = Demande::create([
            'reference' => $request->reference,
            'objet' => $request->objet,
            'date_demande' => $request->date_demande,
            'service_demandeur' => $request->service_demandeur,
        ]);

        /*
        |--------------------------------------------------------------------------
        | 2. Création automatique de la consultation
        |--------------------------------------------------------------------------
        */

        $dateLancement = Carbon::parse($request->date_demande);

        $dateLimite = $dateLancement->copy()->addDays(8);

        $consultation = Consultation::create([
            'demande_achat_id' => $demande->id,
            'reference' => $demande->reference,
            'objet' => $demande->objet,
            'date_lancement' => $dateLancement,
            'date_limite' => $dateLimite,
        ]);

        /*
        |--------------------------------------------------------------------------
        | 3. Redirection vers la consultation créée
        |--------------------------------------------------------------------------
        */

        return redirect()
            ->route('consultation.show', $consultation->id)
            ->with('success', 'Demande et consultation créées avec succès.');
    }

    /**
     * Afficher une demande.
     */
    public function show(Demande $demande)
    {
        return view('demande.show', compact('demande'));
    }

    /**
     * Formulaire de modification.
     */
    public function edit(Demande $demande)
    {
        return view('demande.edit', compact('demande'));
    }

    /**
     * Modifier une demande.
     */
    public function update(Request $request, Demande $demande)
    {
        $request->validate([
            'reference' => 'required|string|max:255',
            'objet' => 'required|string|max:1000',
            'date_demande' => 'required|date',
            'service_demandeur' => 'required|string|max:255',
        ]);

        /*
        |--------------------------------------------------------------------------
        | 1. Mise à jour de la demande
        |--------------------------------------------------------------------------
        */

        $demande->update([
            'reference' => $request->reference,
            'objet' => $request->objet,
            'date_demande' => $request->date_demande,
            'service_demandeur' => $request->service_demandeur,
        ]);

        /*
        |--------------------------------------------------------------------------
        | 2. Récupérer la consultation liée
        |--------------------------------------------------------------------------
        */

        $consultation = Consultation::where(
            'demande_achat_id',
            $demande->id
        )->first();

        /*
        |--------------------------------------------------------------------------
        | 3. Synchroniser la consultation
        |--------------------------------------------------------------------------
        */

        if ($consultation) {
            $consultation->update([
                'reference' => $demande->reference,
                'objet' => $demande->objet,
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | 4. Retour
        |--------------------------------------------------------------------------
        */

        return redirect()
            ->route('demande.index')
            ->with('success', 'Demande modifiée avec succès.');
    }

    /**
     * Supprimer une demande.
     */
    public function destroy(Demande $demande)
    {
        $demande->delete();

        return redirect()
            ->route('demande.index')
            ->with('success', 'Demande supprimée avec succès.');
    }
}
