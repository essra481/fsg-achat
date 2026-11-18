<?php

namespace App\Http\Controllers;

use App\Models\Procesverbal;
use App\Models\consultation;
use App\Models\Signataire;
use App\Exports\ConsultationExport;
use Illuminate\Http\Request;
use Barryvdh\Snappy\Facades\SnappyPdf as Pdf;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;

class procesverbalController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
{
    $consultations = consultation::latest()->get();

    return view('procesverbal.index', compact('consultations'));
}

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return redirect()->route('procesverbal.index');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'consultation_id' => 'required|integer|exists:consultation,id',
            'commentaire' => 'nullable|string|max:5000',
        ]);

        Procesverbal::create([
            'consultation_id' => $request->consultation_id,
            'date_creation' => Carbon::now(),
            'commentaire' => $request->commentaire,
        ]);

        return redirect()
            ->route('procesverbal.index')
            ->with('success', 'PV créé avec succès');
    }

    /**
     * Display the specified resource.
     */
    public function show(Procesverbal $procesverbal)
    {
        $consultation = $procesverbal->consultation;

        $consultation->load([
            'articles',
            'offres.fournisseur'
        ]);

        return view(
            'procesverbal.show',
            compact(
                'procesverbal',
                'consultation'
            )
        );
    }

    /**
     * Show the form for editing the specified resource.
     */
    
/**
 * Show the form for editing the specified resource.
 */
public function edit(Procesverbal $procesverbal)
{
    // Récupérer tous les signataires actifs
    $signataires = Signataire::where('actif', true)
        ->orderBy('nom')
        ->get();

    // Récupérer les signataires déjà associés au PV
    $selectedSignataires = $procesverbal->signataires()
        ->pluck('signataires.id')
        ->toArray();

    return view(
        'procesverbal.edit',
        compact(
            'procesverbal',
            'signataires',
            'selectedSignataires'
        )
    );
}

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Procesverbal $procesverbal)
{
    $validated = $request->validate([
        'commentaire' => 'nullable|string|max:5000',
        'signataires' => 'nullable|array',
        'signataires.*' => 'integer|exists:signataires,id',
    ]);

    \DB::transaction(function () use ($request, $procesverbal) {

        // Enregistrer le commentaire
        $procesverbal->update([
            'commentaire' => $request->commentaire,
        ]);

        // Préparer les signataires avec leur ordre
        $signataires = [];

        foreach ($request->input('signataires', []) as $index => $signataireId) {
            $signataires[$signataireId] = [
                'ordre' => $index + 1,
            ];
        }

        // Synchroniser les associations dans la table pivot
        $procesverbal->signataires()->sync($signataires);
    });

    return redirect()
        ->route('procesverbal.show', $procesverbal->id)
        ->with('success', 'Procès-verbal modifié avec succès.');
}

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Procesverbal $procesverbal)
    {
        $procesverbal->delete();

        return redirect()
            ->route('procesverbal.index')
            ->with('success', 'Procès-verbal supprimé avec succès.');
    }

    /**
     * Show PDF options.
     */
    public function pdfForm($id)
    {
        $procesverbal = Procesverbal::findOrFail($id);

        return view(
            'procesverbal.pdf_options',
            compact('procesverbal')
        );
    }

    /**
     * Generate PDF.
     */
    public function generatePdf(Request $request, $id)
    {
        $procesverbal = Procesverbal::with([
            'consultation.articles',
            'consultation.offres.fournisseur'
        ])->findOrFail($id);

        // Sécurité : seules ces deux valeurs sont transmises à wkhtmltopdf
        $orientation = $request->orientation === 'landscape'
            ? 'landscape'
            : 'portrait';

        $pdf = Pdf::loadView('procesverbal.pdf', [
            'procesverbal' => $procesverbal,
            'consultation' => $procesverbal->consultation,
        ]);

        $pdf->setOption(
            'orientation',
            ucfirst($orientation)
        );
       $pdf->setOption(
            'footer-center',
            '[page]/[topage]'
        );

        $pdf->setOption(
            'footer-font-size',
            9
        );

        $pdf->setOption(
            'footer-spacing',
            5
        );

        return $pdf->inline(
            "Proces_Verbal_{$id}.pdf"
        );
    }

    /**
     * Export PV to Excel.
     */
    public function excel($id)
    {
        /*
        |--------------------------------------------------------------------------
        | Récupération de la consultation
        |--------------------------------------------------------------------------
        */

        $consultation = consultation::with([
            'articles',
            'offres.fournisseur',
            'procesverbal'
        ])->findOrFail($id);

        /*
        |--------------------------------------------------------------------------
        | Nettoyage de la référence pour le nom du fichier
        |--------------------------------------------------------------------------
        |
        | Exemple :
        |
        | 1/2026
        |
        | devient :
        |
        | 1_2026
        |
        | afin d'éviter l'erreur avec "/".
        |--------------------------------------------------------------------------
        */

        $reference = str_replace(
            ['/', '\\'],
            '_',
            $consultation->reference
        );

        /*
        |--------------------------------------------------------------------------
        | Nom du fichier
        |--------------------------------------------------------------------------
        */

        $filename = 'Proces_Verbal_' . $reference . '.xlsx';

        /*
        |--------------------------------------------------------------------------
        | Export Excel
        |--------------------------------------------------------------------------
        */

        return Excel::download(
            new ConsultationExport(
                $consultation,
                'pv'
            ),
            $filename
        );
    }
}