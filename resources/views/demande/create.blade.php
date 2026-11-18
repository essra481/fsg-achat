@extends('layouts.app')

@section('title', 'Nouvelle demande d\'achat')

@section('content')

<h1 class="h3 mb-4">Nouvelle demande d'achat</h1>

<div class="card p-4" style="max-width: 600px;">

    <form method="POST" action="{{ route('demande.store') }}">
        @csrf

        {{-- Référence --}}
        <div class="mb-3">
            <label class="form-label">
                Référence de la demande
            </label>

            <input
                type="text"
                name="reference"
                class="form-control"
                placeholder="Exemple : 07/2026"
                value="{{ old('reference') }}"
                required
            >

           
        </div>

        {{-- Objet --}}
        <div class="mb-3">
            <label class="form-label">
                Objet de la demande
            </label>

            <input
                type="text"
                name="objet"
                class="form-control"
                required
                value="{{ old('objet') }}"
            >
        </div>

        {{-- Service --}}
        <div class="mb-3">
            <label class="form-label">
                Service demandeur
            </label>

            <input
                type="text"
                name="service_demandeur"
                class="form-control"
                value="{{ old('service_demandeur') }}"
            >
        </div>

        {{-- Date --}}
        <div class="mb-3">
            <label class="form-label">
                Date de la demande
            </label>

            <input
                type="date"
                name="date_demande"
                class="form-control"
                required
                value="{{ old('date_demande', date('Y-m-d')) }}"
            >
        </div>

        {{-- Erreurs --}}
        @if($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <button type="submit" class="btn btn-primary">
            Enregistrer
        </button>

        <a href="{{ route('demande.index') }}" class="btn btn-link">
            Annuler
        </a>

    </form>

</div>

@endsection
