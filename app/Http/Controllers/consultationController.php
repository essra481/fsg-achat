<?php

namespace App\Http\Controllers;

use App\Models\consultation;
use App\Models\Article;
use App\Models\Signataire;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Exports\ConsultationExport;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\Snappy\Facades\SnappyPdf as Pdf;

class consultationController extends Controller
{
    /**
     * Afficher la liste des consultations.
     */
   public function index()
{
    $consultation = consultation::latest()
        ->paginate(10);

    return view('consultation.index', compact('consultation'));
}

    /**
     * Les consultations sont créées automatiquement
     * après la création d'une demande.
     */
    public function create()
    {
        return redirect()->route('consultation.index');
    }

    /**
     * Non utilisé.
     */
    public function store(Request $request)
    {
        return redirect()->route('consultation.index');
    }

    /**
     * Afficher une consultation.
     */
    public function show(consultation $consultation)
    {
        $consultation->load('articles');

        $doyen = Signataire::where('type', 'العميد')
            ->where('actif', true)
            ->first();

        return view(
            'consultation.show',
            compact('consultation', 'doyen')
        );
    }

    /**
     * Formulaire de modification.
     */
    public function edit(consultation $consultation)
    {
        return view('consultation.edit', compact('consultation'));
    }

    /**
     * Mettre à jour une consultation.
     *
     * L'objet peut être modifié.
     * Les dates peuvent être NULL après un import.
     * Elles peuvent ensuite être ajoutées ou modifiées.
     */
    public function update(Request $request, consultation $consultation)
    {
        $request->validate([
            'objet' => 'required|string|max:1000',
            'date_lancement' => 'nullable|date',
            'date_limite' => 'nullable|date',
        ], [
            'objet.required' => 'L\'objet est obligatoire.',
            'objet.max' => 'L\'objet ne doit pas dépasser 1000 caractères.',
            'date_lancement.date' => 'La date de lancement est invalide.',
            'date_limite.date' => 'La date limite est invalide.',
        ]);

        $consultation->update([
            'objet' => $request->objet,
            'date_lancement' => $request->date_lancement ?: null,
            'date_limite' => $request->date_limite ?: null,
        ]);

        return redirect()
            ->route('consultation.index')
            ->with('success', 'Consultation modifiée avec succès.');
    }

    /**
     * Supprimer une consultation.
     */
    public function destroy(consultation $consultation)
    {
        $consultation->delete();

        return redirect()
            ->route('consultation.index')
            ->with('success', 'Consultation supprimée avec succès.');
    }

    /**
     * Générer le PDF avec Snappy.
     */
    public function pdf(consultation $consultation)
    {
        $consultation->load('articles');

        $doyen = Signataire::where('type', 'العميد')
            ->where('actif', true)
            ->first();

        $pdf = Pdf::loadView(
            'consultation.pdf',
            compact('consultation', 'doyen')
        )
            ->setOption('page-size', 'A4')
            ->setOption('orientation', 'Portrait')
            ->setOption('margin-top', '10mm')
            ->setOption('margin-bottom', '10mm')
            ->setOption('margin-left', '10mm')
            ->setOption('margin-right', '10mm')
            ->setOption('footer-center', '[page]/[topage]')
            ->setOption('footer-font-size', 9)
            ->setOption('footer-spacing', 5);

        $filename = 'Consultation_' .
            str_replace('/', '-', $consultation->reference) .
            '.pdf';

        $pdf->setOption('enable-local-file-access', true);

        return $pdf->stream($filename);
    }

    /**
     * Affichage pour impression.
     */
    public function print(consultation $consultation)
    {
        $consultation->load('articles');

        $doyen = Signataire::where('type', 'العميد')
            ->where('actif', true)
            ->first();

        return view(
            'consultation.print',
            compact('consultation', 'doyen')
        );
    }

    /**
     * Enregistrer les articles.
     */
    public function saveArticles(
        Request $request,
        consultation $consultation
    ) {
        // Validation des données envoyées (avant toute suppression)
        $request->validate([
            'designation' => ['nullable', 'array', 'max:500'],
            'designation.*' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'array'],
            'description.*' => ['nullable', 'string', 'max:5000'],
            'quantite' => ['nullable', 'array'],
            'quantite.*' => ['nullable', 'integer', 'min:0'],
            'prix_unitaire' => ['nullable', 'array'],
            'prix_unitaire.*' => ['nullable', 'string', 'max:50'],
            'prix_total' => ['nullable', 'array'],
            'prix_total.*' => ['nullable', 'string', 'max:50'],
        ]);

        // Transaction : si une insertion échoue, les anciens articles
        // ne sont pas perdus.
        DB::transaction(function () use ($request, $consultation) {

            // Supprimer les anciens articles
            $consultation->articles()->delete();

            if ($request->designation) {

                foreach ($request->designation as $i => $designation) {

                    if (trim($designation) != '') {

                        Article::create([
                            'consultation_id' => $consultation->id,

                            'designation' => $designation,

                            // Description
                            'description' =>
                                $request->description[$i] ?? null,

                            // Quantité
                            'quantite' =>
                                $request->quantite[$i] ?? 0,

                            // Prix unitaire
                            'prix_unitaire' =>
                                $request->prix_unitaire[$i] ?? 0,

                            // Prix total
                            'prix_total' =>
                                $request->prix_total[$i] ?? 0,
                        ]);
                    }
                }
            }
        });

        return redirect()
            ->route(
                'consultation.show',
                $consultation->id
            )
            ->with(
                'success',
                'Consultation enregistrée avec succès.'
            );
    }

    /**
     * Afficher le document.
     */
    public function document(consultation $consultation)
    {
        $consultation->load('articles');

        $doyen = Signataire::where('type', 'العميد')
            ->where('actif', true)
            ->first();

        return view(
            'consultation.document',
            compact('consultation', 'doyen')
        );
    }

    /**
     * Export Excel ancien format .xls.
     */
    public function excel($id)
    {
        $consultation = consultation::with('articles')
            ->findOrFail($id);

        $html = view(
            'consultation.excel',
            compact('consultation')
        )->render();

        return response($html)
            ->header(
                'Content-Type',
                'application/vnd.ms-excel'
            )
            ->header(
                'Content-Disposition',
                'attachment; filename="Consultation_' .
                $consultation->reference .
                '.xls"'
            );
    }

    /**
     * Export Excel avec ConsultationExport.
     */
    public function exportExcel($id)
{
    $consultation = consultation::with('articles')
        ->findOrFail($id);

    // Récupérer le doyen actif
    $doyen = Signataire::where('type', 'العميد')
        ->where('actif', true)
        ->first();

    // Nettoyer la référence pour éviter / ou \
    // dans le nom du fichier.
    $reference = str_replace(
        ['/', '\\'],
        '_',
        $consultation->reference
    );

    $filename = 'consultation_' .
        $reference .
        '.xlsx';

    return Excel::download(
        new ConsultationExport($consultation, $doyen),
        $filename
    );
}
}