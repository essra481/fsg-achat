<?php

namespace App\Http\Controllers;

use App\Models\Signataire;
use Illuminate\Http\Request;

class SignataireController extends Controller
{
    public function index()
    {
        $signataires = Signataire::orderBy('type')
            ->orderBy('nom')
            ->get();

        return view('signataires.index', compact('signataires'));
    }

    public function create()
    {
        return view('signataires.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nom' => ['required', 'string', 'max:255'],
            'fonction' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'max:100'],
            'date_debut' => ['nullable', 'date'],
            'date_fin' => [
                'nullable',
                'date',
                'after_or_equal:date_debut'
            ],
        ]);

        $validated['actif'] = true;

        Signataire::create($validated);

        return redirect()
            ->route('signataires.index')
            ->with('success', 'Signataire ajouté avec succès.');
    }

    public function edit(Signataire $signataire)
    {
        return view(
            'signataires.edit',
            compact('signataire')
        );
    }

    public function update(Request $request, Signataire $signataire)
    {
        $validated = $request->validate([
            'nom' => ['required', 'string', 'max:255'],
            'fonction' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'max:100'],
            'date_debut' => ['nullable', 'date'],
            'date_fin' => [
                'nullable',
                'date',
                'after_or_equal:date_debut'
            ],
        ]);

        $validated['actif'] = $request->boolean('actif');

        $signataire->update($validated);

        return redirect()
            ->route('signataires.index')
            ->with('success', 'Signataire modifié avec succès.');
    }

    public function destroy(Signataire $signataire)
    {
        $signataire->update([
            'actif' => false,
            'date_fin' => now()->toDateString(),
        ]);

        return redirect()
            ->route('signataires.index')
            ->with('success', 'Signataire désactivé avec succès.');
    }

    public function forceDelete(Signataire $signataire)
    {
        $signataire->delete();

        return redirect()
            ->route('signataires.index')
            ->with('success', 'Signataire supprimé définitivement.');
    }
}