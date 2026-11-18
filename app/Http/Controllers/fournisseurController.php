<?php

namespace App\Http\Controllers;

use App\Models\fournisseurs;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class fournisseurController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
{
    $fournisseurs = fournisseurs::latest()
        ->paginate(10);

    return view('fournisseurs.index', compact('fournisseurs'));
}

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('fournisseurs.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->merge([
            'matricule_fiscal' => $request->filled('matricule_fiscal')
                ? $request->matricule_fiscal
                : null,

            'email' => $request->filled('email')
                ? $request->email
                : null,

            'telephone' => $request->filled('telephone')
                ? $request->telephone
                : null,
        ]);
      $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                'unique:fournisseurs,name',
            ],

            'matricule_fiscal' => [
                'nullable',
                'string',
                'unique:fournisseurs,matricule_fiscal',
            ],

            'email' => [
                'nullable',
                'email',
                'unique:fournisseurs,email',
            ],

            'telephone' => [
                'nullable',
                'string',
                'unique:fournisseurs,telephone',
            ],
        ], [
            'name.required' => 'Le nom du fournisseur est obligatoire.',
            'name.unique' => 'Ce nom de fournisseur existe déjà.',

            'matricule_fiscal.unique' => 'Ce matricule fiscal existe déjà.',

            'email.email' => 'Veuillez saisir une adresse email valide.',
            'email.unique' => 'Cette adresse email existe déjà.',

            'telephone.unique' => 'Ce numéro de téléphone existe déjà.',
        ]);

        fournisseurs::create([
            'name' => $request->name,
            'matricule_fiscal' => $request->matricule_fiscal ?: null,
            'email' => $request->email ?: null,
            'telephone' => $request->telephone ?: null,
        ]);

        return redirect()
            ->route('fournisseurs.index')
            ->with('success', 'Fournisseur ajouté avec succès.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Fournisseurs $fournisseur)
    {
        return view('fournisseurs.show', compact('fournisseur'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Fournisseurs $fournisseur)
    {
        return view('fournisseurs.edit', compact('fournisseur'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Fournisseurs $fournisseur)
    {
        $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('fournisseurs', 'name')
                    ->ignore($fournisseur->id),
            ],

            'matricule_fiscal' => [
               
                'string',
                'max:255',
                Rule::unique('fournisseurs', 'matricule_fiscal')
                    ->ignore($fournisseur->id),
            ],

            'email' => [
               
                'email',
                'max:255',
                Rule::unique('fournisseurs', 'email')
                    ->ignore($fournisseur->id),
            ],

            'telephone' => [
               
                'string',
                'max:50',
                Rule::unique('fournisseurs', 'telephone')
                    ->ignore($fournisseur->id),
            ],
        ], [
            'name.required' => 'Le nom du fournisseur est obligatoire.',
            'name.unique' => 'Ce nom de fournisseur existe déjà.',

          
            'matricule_fiscal.unique' => 'Ce matricule fiscal existe déjà.',

         
            'email.email' => 'Veuillez saisir une adresse email valide.',
            'email.unique' => 'Cette adresse email existe déjà.',

        
            'telephone.unique' => 'Ce numéro de téléphone existe déjà.',
        ]);

        $fournisseur->update([
            'name' => $request->name,
            'matricule_fiscal' => $request->matricule_fiscal,
            'email' => $request->email,
            'telephone' => $request->telephone,
        ]);

        return redirect()
            ->route('fournisseurs.index')
            ->with('success', 'Fournisseur modifié avec succès');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Fournisseurs $fournisseur)
    {
        $fournisseur->delete();

        return redirect()
            ->route('fournisseurs.index')
            ->with('success', 'Fournisseur supprimé');
    }
}