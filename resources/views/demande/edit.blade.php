@extends('layouts.app')

@section('title', 'Modifier demande')

@section('content')

<h1 class="h3 mb-4">Modifier demande</h1>

<div class="card p-4" style="max-width: 600px;">

    <form
        action="{{ route('demande.update', $demande->id) }}"
        method="POST"
    >

        @csrf
        @method('PUT')

        {{-- Référence --}}
        <div class="mb-3">
            <label class="form-label">
                Référence de la demande
            </label>

            <input
                type="text"
                name="reference"
                value="{{ old('reference', $demande->reference) }}"
                class="form-control"
                placeholder="Exemple : 07/2026"
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
                value="{{ old('objet', $demande->objet) }}"
                class="form-control"
                required
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
                value="{{ old('date_demande', $demande->date_demande) }}"
                class="form-control"
                required
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
                value="{{ old('service_demandeur', $demande->service_demandeur) }}"
                class="form-control"
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

        <button class="btn btn-success">
            Modifier
        </button>

        <a
            href="{{ route('demande.index') }}"
            class="btn btn-link"
        >
            Annuler
        </a>

    </form>

</div>

@endsection