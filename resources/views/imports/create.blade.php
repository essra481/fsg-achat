@extends('layouts.app')

@section('title', 'Importer Excel')

@section('content')

<div class="container-fluid">

    {{-- En-tête --}}
    <div class="mb-4">
        <h2 class="fw-bold mb-1">
            <i class="fa-solid fa-file-import text-primary me-2"></i>
            Import Excel
        </h2>

        <p class="text-muted mb-0">
            Importez un fichier Excel contenant une consultation,
            un procès-verbal ou une répartition.
        </p>
    </div>


   


    {{-- Erreurs de validation --}}
    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fa-solid fa-circle-exclamation me-2"></i>

            <strong>Import impossible :</strong>

            <ul class="mb-0 mt-2">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>

            <button type="button"
                    class="btn-close"
                    data-bs-dismiss="alert">
            </button>
        </div>
    @endif


    {{-- Carte principale --}}
    <div class="card shadow-sm border-0">

        <div class="card-header bg-white border-0 pt-4 px-4">
            <h5 class="fw-bold mb-1">
                <i class="fa-solid fa-upload me-2 text-primary"></i>
                Sélectionner un fichier
            </h5>

            <p class="text-muted small mb-0">
                Choisissez le type de document et le fichier Excel à analyser.
            </p>
        </div>


        <div class="card-body p-4">

            {{-- IMPORTANT :
                 on utilise imports.sheets et non imports.preview
            --}}
            <form method="POST"
                  action="{{ route('imports.sheets') }}"
                  enctype="multipart/form-data">

                @csrf


                <div class="row g-4">

                    {{-- Type d'import --}}
                    <div class="col-md-6">

                        <label for="import_type"
                               class="form-label fw-semibold">

                            <i class="fa-solid fa-layer-group me-1 text-primary"></i>
                            Type d'import
                        </label>

                        <select name="import_type"
                                id="import_type"
                                class="form-select"
                                required>

                            <option value="">
                                -- Sélectionner le type --
                            </option>

                            @foreach($types as $key => $label)

                                <option value="{{ $key }}"
                                    {{ old('import_type') == $key ? 'selected' : '' }}>

                                    {{ $label }}

                                </option>

                            @endforeach

                        </select>

                        @error('import_type')
                            <div class="text-danger small mt-1">
                                {{ $message }}
                            </div>
                        @enderror

                    </div>


                    {{-- Service demandeur --}}
                    <div class="col-md-6">

                        <label for="service_demandeur"
                               class="form-label fw-semibold">

                            <i class="fa-solid fa-building me-1 text-primary"></i>
                            Service demandeur
                        </label>

                        <input type="text"
                               name="service_demandeur"
                               id="service_demandeur"
                               class="form-control"
                               value="{{ old('service_demandeur') }}"
                               placeholder="Ex. Service financier"
                               required>

                        @error('service_demandeur')
                            <div class="text-danger small mt-1">
                                {{ $message }}
                            </div>
                        @enderror

                    </div>


                    {{-- Fichier Excel --}}
                    <div class="col-12">

                        <label for="file"
                               class="form-label fw-semibold">

                            <i class="fa-solid fa-file-excel me-1 text-success"></i>
                            Fichier Excel
                        </label>

                        <input type="file"
                               name="file"
                               id="file"
                               class="form-control"
                               accept=".xlsx,.xls"
                               required>

                        <div class="form-text">
                            Formats acceptés : <strong>.xlsx</strong> et
                            <strong>.xls</strong>.
                            Taille maximale : 20 Mo.
                        </div>

                        @error('file')
                            <div class="text-danger small mt-1">
                                {{ $message }}
                            </div>
                        @enderror

                    </div>

                </div>


                {{-- Boutons --}}
                <div class="d-flex justify-content-between align-items-center mt-4">

                    <a href="{{ route('consultation.index') }}"
                       class="btn btn-outline-secondary">

                        <i class="fa-solid fa-arrow-left me-1"></i>
                        Retour
                    </a>


                    <button type="submit"
                            class="btn btn-primary px-4">

                        <i class="fa-solid fa-table-list me-1"></i>
                        Choisir la feuille

                    </button>

                </div>

            </form>

        </div>

    </div>


    {{-- Informations --}}
    <div class="card shadow-sm border-0 mt-4">

        <div class="card-body p-4">

            <h6 class="fw-bold mb-3">
                <i class="fa-solid fa-circle-info text-primary me-2"></i>
                Fonctionnement de l'import
            </h6>

            <div class="row g-3">

                <div class="col-md-3">
                    <div class="d-flex gap-3">

                        <div class="text-primary fs-4">
                            <i class="fa-solid fa-1"></i>
                        </div>

                        <div>
                            <strong>Choisir le fichier</strong>
                            <div class="text-muted small">
                                Sélectionnez votre fichier Excel.
                            </div>
                        </div>

                    </div>
                </div>


                <div class="col-md-3">
                    <div class="d-flex gap-3">

                        <div class="text-primary fs-4">
                            <i class="fa-solid fa-2"></i>
                        </div>

                        <div>
                            <strong>Choisir la feuille</strong>
                            <div class="text-muted small">
                                Si le fichier contient plusieurs feuilles,
                                choisissez celle à importer.
                            </div>
                        </div>

                    </div>
                </div>


                <div class="col-md-3">
                    <div class="d-flex gap-3">

                        <div class="text-primary fs-4">
                            <i class="fa-solid fa-3"></i>
                        </div>

                        <div>
                            <strong>Analyser</strong>
                            <div class="text-muted small">
                                Le système détecte automatiquement les données.
                            </div>
                        </div>

                    </div>
                </div>


                <div class="col-md-3">
                    <div class="d-flex gap-3">

                        <div class="text-primary fs-4">
                            <i class="fa-solid fa-4"></i>
                        </div>

                        <div>
                            <strong>Confirmer</strong>
                            <div class="text-muted small">
                                Vérifiez l'aperçu avant l'import définitif.
                            </div>
                        </div>

                    </div>
                </div>

            </div>

        </div>

    </div>


    {{-- Détection automatique --}}
    <div class="alert alert-info mt-4 border-0 shadow-sm">

        <div class="d-flex gap-3">

            <div class="fs-4">
                <i class="fa-solid fa-wand-magic-sparkles"></i>
            </div>

            <div>

                <strong>Détection automatique</strong>

                <p class="mb-0 mt-1 small">

                    Le système peut rechercher automatiquement la référence
                    après <strong>« إستشارة عدد »</strong>, les articles,
                    les quantités, les descriptions et les informations
                    des fournisseurs.

                    Les valeurs <strong>NC</strong> et <strong>ND</strong>
                    sont également reconnues.

                </p>

            </div>

        </div>

    </div>

</div>

@endsection