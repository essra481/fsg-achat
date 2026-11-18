@extends('layouts.app')

@section('title', 'Nouvelle consultation')

@section('content')
<h1 class="h3 mb-4">Generer une consultation</h1>
<p class="text-muted">A partir de la demande : <strong>{{ $demande->objet }}</strong></p>

<div class="card p-4" style="max-width: 600px;">
    <form method="POST" action="{{ route('consultation.store') }}">
        @csrf
        <input type="hidden" name="demande_achat_id" value="{{ $demande->id }}">
        <div class="mb-3">
            <label class="form-label">Objet de la consultation</label>
            <input type="text" name="objet" class="form-control" required value="{{ old('objet', $demande->objet) }}">
        </div>
        <div class="mb-3">
            <label class="form-label">Date de lancement</label>
            <input type="date" name="date_lancement" class="form-control" required value="{{ old('date_lancement', date('Y-m-d')) }}">
        </div>
        <div class="mb-3">
            <label class="form-label">Date limite de depot des offres</label>
            <input type="date" name="date_limite" class="form-control" required value="{{ old('date_limite') }}">
        </div>
        @if($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        <button type="submit" class="btn btn-primary">Generer la consultation</button>
        <a href="{{ route('demande.show', $demande) }}" class="btn btn-link">Annuler</a>
    </form>
</div>
@endsection
