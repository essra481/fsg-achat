@extends('layouts.app')

@section('title', 'Choisir la feuille Excel')

@section('content')

<div class="container-fluid">

    {{-- En-tête --}}
    <div class="mb-4">

        <h2 class="fw-bold mb-1">
            <i class="fa-solid fa-table-list text-primary me-2"></i>
            Choisir la feuille Excel
        </h2>

        <p class="text-muted mb-0">
            Votre fichier contient plusieurs feuilles.
            Sélectionnez la feuille qui contient les données à importer.
        </p>

    </div>


    {{-- Message d'erreur --}}
    @if(session('error'))

        <div class="alert alert-danger alert-dismissible fade show"
             role="alert">

            <i class="fa-solid fa-circle-exclamation me-2"></i>

            {{ session('error') }}

            <button type="button"
                    class="btn-close"
                    data-bs-dismiss="alert">
            </button>

        </div>

    @endif


    {{-- Erreurs de validation --}}
    @if($errors->any())

        <div class="alert alert-danger">

            <strong>Erreur :</strong>

            <ul class="mb-0 mt-2">

                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach

            </ul>

        </div>

    @endif


    {{-- Carte --}}
    <div class="card shadow-sm border-0">

        <div class="card-header bg-white border-0 pt-4 px-4">

            <div class="d-flex align-items-center">

                <div class="bg-primary bg-opacity-10 rounded p-3 me-3">

                    <i class="fa-solid fa-file-excel text-primary fs-4"></i>

                </div>

                <div>

                    <h5 class="fw-bold mb-1">
                        Feuilles disponibles
                    </h5>

                    <p class="text-muted small mb-0">
                        Une seule feuille sera analysée et importée.
                    </p>

                </div>

            </div>

        </div>


        <div class="card-body p-4">

            <form method="POST"
                  action="{{ route('imports.preview') }}">

                @csrf


                <div class="mb-4">

                    <label for="sheet"
                           class="form-label fw-semibold">

                        <i class="fa-solid fa-table me-1 text-primary"></i>

                        Feuille à importer

                    </label>


                    <select name="sheet"
                            id="sheet"
                            class="form-select form-select-lg"
                            required>

                        <option value="">
                            -- Choisir une feuille --
                        </option>

                        @foreach($sheets as $sheet)

                            <option value="{{ $sheet }}">

                                {{ $sheet }}

                            </option>

                        @endforeach

                    </select>


                    <div class="form-text mt-2">

                        <i class="fa-solid fa-circle-info me-1"></i>

                        Les autres feuilles ne seront pas analysées.

                    </div>

                </div>


                {{-- Liste visuelle des feuilles --}}
                <div class="mb-4">

                    <label class="form-label fw-semibold">
                        Feuilles détectées
                    </label>

                    <div class="list-group">

                        @foreach($sheets as $index => $sheet)

                            <div class="list-group-item d-flex align-items-center">

                                <div class="me-3">

                                    <span class="badge bg-primary rounded-pill">
                                        {{ $index + 1 }}
                                    </span>

                                </div>

                                <div class="flex-grow-1">

                                    <i class="fa-solid fa-table me-2 text-success"></i>

                                    {{ $sheet }}

                                </div>

                            </div>

                        @endforeach

                    </div>

                </div>


                {{-- Boutons --}}
                <div class="d-flex justify-content-between align-items-center">

                    <a href="{{ route('imports.create') }}"
                       class="btn btn-outline-secondary">

                        <i class="fa-solid fa-arrow-left me-1"></i>

                        Retour

                    </a>


                    <button type="submit"
                            class="btn btn-primary px-4">

                        <i class="fa-solid fa-magnifying-glass me-1"></i>

                        Analyser cette feuille

                    </button>

                </div>

            </form>

        </div>

    </div>


    {{-- Information --}}
    <div class="alert alert-info border-0 shadow-sm mt-4">

        <div class="d-flex gap-3">

            <div class="fs-4">
                <i class="fa-solid fa-circle-info"></i>
            </div>

            <div>

                <strong>Attention</strong>

                <p class="mb-0 mt-1 small">

                    Le système analysera uniquement la feuille que vous
                    sélectionnez ici. Les données des autres feuilles
                    resteront inchangées et ne seront pas importées.

                </p>

            </div>

        </div>

    </div>

</div>

@endsection