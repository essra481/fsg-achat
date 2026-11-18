@extends('layouts.app')

@section('title', $consultation->reference)

@section('content')
<div class="d-flex justify-content-between align-items-start mb-4">
    <div>
        <h1 class="h3 mb-1">{{ $consultation->reference }}</h1>
        <p class="text-muted mb-0">{{ $consultation->objet }}</p>
    </div>
    <div class="text-end">
        <span class="badge bg-secondary badge-statut mb-2">{{ $consultation->statut }}</span><br>
        @if($consultation->statut === 'ouverte')
            <form method="POST" action="{{ route('consultation.cloturer', $consultation) }}" class="d-inline">
                @csrf
                <button class="btn btn-sm btn-outline-secondary">Cloturer la consultation</button>
            </form>
        @endif
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="card p-3 h-100">
            <dl class="row mb-0">
                <dt class="col-sm-5">Date de lancement</dt>
                <dd class="col-sm-7">{{ \Carbon\Carbon::parse($consultation->date_lancement)->format('d/m/Y') }}</dd>
                <dt class="col-sm-5">Date limite</dt>
                <dd class="col-sm-7">{{ \Carbon\Carbon::parse($consultation->date_limite)->format('d/m/Y') }}
                    @if($consultation->estEnRetard())<span class="badge bg-danger">en retard</span>@endif
                </dd>
                <dt class="col-sm-5">Demande d'achat liee</dt>
                <dd class="col-sm-7">
                    @if($consultation->demandeAchat)
                        <a href="{{ route('demande.show', $consultation->demandeAchat) }}">{{ $consultation->demandeAchat->objet }}</a>
                    @else — @endif
                </dd>
                <dt class="col-sm-5">Responsable</dt>
                <dd class="col-sm-7">{{ $consultation->utilisateur->name }}</dd>
            </dl>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card p-3 h-100">
            <h2 class="h6">Proces-verbal</h2>
            @if($consultation->procesVerbal)
                <p class="mb-2">Reference : <a href="{{ route('proces-verbal.show', $consultation->procesVerbal) }}">{{ $consultation->procesVerbal->reference }}</a></p>
                <p class="mb-0">Statut : <span class="badge bg-secondary badge-statut">{{ $consultation->procesVerbal->statut }}</span></p>
            @else
                <p class="text-muted">Aucun PV genere pour le moment.</p>
                <form method="POST" action="{{ route('proces-verbal.generer', $consultation) }}">
                    @csrf
                    <button class="btn btn-primary btn-sm" {{ $consultation->offres->isEmpty() ? 'disabled' : '' }}>
                        Generer le PV (comparaison des offres)
                    </button>
                </form>
                @if($consultation->offres->isEmpty())
                    <small class="text-muted">Ajoutez au moins une offre fournisseur pour generer le PV.</small>
                @endif
            @endif
        </div>
    </div>
</div>

<div class="card p-3 mb-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="h6 mb-0">Offres des fournisseurs</h2>
        @if($consultation->statut === 'ouverte')
        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modalOffre">Ajouter une offre</button>
        @endif
    </div>
    <table class="table table-sm align-middle">
        <thead><tr><th>Fournisseur</th><th>Montant (TND)</th><th>Date de depot</th><th>Statut</th><th></th></tr></thead>
        <tbody>
        @forelse($consultation->offres as $offre)
            <tr>
                <td><a href="{{ route('fournisseurs.show', $offre->fournisseur) }}">{{ $offre->fournisseur->raison_sociale }}</a></td>
                <td>{{ number_format($offre->montant, 3) }}</td>
                <td>{{ \Carbon\Carbon::parse($offre->date_depot)->format('d/m/Y') }}</td>
                <td>
                    @php
                        $badgeClass = match($offre->statut) {
                            'retenue' => 'bg-success',
                            'rejetee' => 'bg-danger',
                            default => 'bg-secondary',
                        };
                    @endphp
                    <span class="badge {{ $badgeClass }} badge-statut">{{ $offre->statut }}</span>
                </td>
                <td>
                    @if($consultation->statut === 'ouverte' && !$consultation->procesVerbal)
                    <form method="POST" action="{{ route('offre.destroy', [$consultation, $offre]) }}" onsubmit="return confirm('Supprimer cette offre ?')">
                        @csrf @method('DELETE')
                        <button class="btn btn-sm btn-link text-danger p-0">Supprimer</button>
                    </form>
                    @endif
                </td>
            </tr>
        @empty
            <tr><td colspan="5" class="text-center text-muted py-3">Aucune offre deposee.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>

<!-- Modal ajout offre -->
<div class="modal fade" id="modalOffre" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('offre.store', $consultation) }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Ajouter une offre fournisseur</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Fournisseur</label>
                        <select name="fournisseur_id" class="form-select" required>
                            <option value="">— Choisir —</option>
                            @foreach($fournisseurs as $f)
                                <option value="{{ $f->id }}">{{ $f->raison_sociale }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Montant de l'offre (TND)</label>
                        <input type="number" step="0.001" name="montant" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Date de depot</label>
                        <input type="date" name="date_depot" class="form-control" required value="{{ date('Y-m-d') }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Observations</label>
                        <textarea name="observations" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Enregistrer l'offre</button>
                </div>
            </form>
        </div>
    </div>
</div>

<a href="{{ route('consultation.index') }}" class="btn btn-link">Retour a la liste</a>
@endsection
