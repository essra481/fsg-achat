@extends('layouts.app')

@section('title', 'Aperçu de l’import')

@section('content')

<div class="container-fluid">

    {{-- =========================================================
         EN-TÊTE
    ========================================================== --}}
    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>
            <h2 class="fw-bold mb-1">
                <i class="fa-solid fa-magnifying-glass text-primary me-2"></i>
                Aperçu de l’import
            </h2>

            <p class="text-muted mb-0">
                Vérifiez les données détectées avant de confirmer l’importation.
            </p>
        </div>

        <a href="{{ route('imports.create') }}"
           class="btn btn-outline-secondary">

            <i class="fa-solid fa-arrow-left me-1"></i>
            Annuler
        </a>

    </div>


    {{-- =========================================================
         ERREURS
    ========================================================== --}}
    @if(!empty($summary['errors']))

        <div class="alert alert-danger shadow-sm">

            <div class="fw-bold mb-2">
                <i class="fa-solid fa-circle-exclamation me-2"></i>
                Erreurs détectées
            </div>

            <ul class="mb-0">

                @foreach($summary['errors'] as $error)

                    <li>
                        {{ $error }}
                    </li>

                @endforeach

            </ul>

        </div>

    @endif


    {{-- =========================================================
         AVERTISSEMENTS
    ========================================================== --}}
    @if(!empty($summary['warnings']))

        <div class="alert alert-warning shadow-sm">

            <div class="fw-bold mb-2">
                <i class="fa-solid fa-triangle-exclamation me-2"></i>
                Avertissements
            </div>

            <ul class="mb-0">

                @foreach($summary['warnings'] as $warning)

                    <li>
                        {{ $warning }}
                    </li>

                @endforeach

            </ul>

        </div>

    @endif


    {{-- =========================================================
         INFORMATIONS GÉNÉRALES
    ========================================================== --}}
    <div class="card shadow-sm border-0 mb-4">

        <div class="card-header bg-white border-0 pt-4 px-4">

            <h5 class="fw-bold mb-0">
                <i class="fa-solid fa-circle-info text-primary me-2"></i>
                Informations détectées
            </h5>

        </div>


        <div class="card-body px-4 pb-4">

            <div class="row g-4">

                {{-- Référence --}}
                <div class="col-md-4">

                    <label class="form-label text-muted small mb-1">
                        Référence
                    </label>

                    <div class="fw-bold fs-5">

                        @if(!empty($summary['reference']))

                            <span class="badge bg-primary fs-6">
                                {{ $summary['reference'] }}
                            </span>

                        @else

                            <span class="text-danger">
                                <i class="fa-solid fa-circle-exclamation me-1"></i>
                                Non détectée
                            </span>

                        @endif

                    </div>

                </div>


                {{-- Objet --}}
                <div class="col-md-8">

                    <label class="form-label text-muted small mb-1">
                        Objet
                    </label>

                    <div class="fw-semibold">

                        {{ $summary['objet'] ?? 'Non détecté' }}

                    </div>

                </div>


                {{-- Date lancement --}}
                <div class="col-md-4">

                    <label class="form-label text-muted small mb-1">
                        Date de lancement
                    </label>

                    <div>

                        @if(!empty($summary['date_lancement']))

                            {{ $summary['date_lancement'] }}

                        @else

                            <span class="text-muted">
                                Non renseignée
                            </span>

                        @endif

                    </div>

                </div>


                {{-- Date limite --}}
                <div class="col-md-4">

                    <label class="form-label text-muted small mb-1">
                        Date limite
                    </label>

                    <div>

                        @if(!empty($summary['date_limite']))

                            {{ $summary['date_limite'] }}

                        @else

                            <span class="text-muted">
                                Non renseignée
                            </span>

                        @endif

                    </div>

                </div>


                {{-- Service --}}
                <div class="col-md-4">

                    <label class="form-label text-muted small mb-1">
                        Service demandeur
                    </label>

                    <div>

                        {{ $summary['service_demandeur'] ?? session('procurement_import.service') ?? 'Non renseigné' }}

                    </div>

                </div>

            </div>

        </div>

    </div>


    {{-- =========================================================
         ARTICLES
    ========================================================== --}}
    <div class="card shadow-sm border-0 mb-4">

        <div class="card-header bg-white border-0 pt-4 px-4">

            <div class="d-flex justify-content-between align-items-center">

                <div>

                    <h5 class="fw-bold mb-1">

                        <i class="fa-solid fa-boxes-stacked text-primary me-2"></i>

                        Articles

                    </h5>

                    <p class="text-muted small mb-0">
                        Articles détectés dans la feuille sélectionnée.
                    </p>

                </div>


                <span class="badge bg-primary fs-6">

                    {{ count($summary['articles'] ?? []) }}

                    article(s)

                </span>

            </div>

        </div>


        <div class="card-body p-0">

            @if(!empty($summary['articles']))

                <div class="table-responsive">

                    <table class="table table-hover align-middle mb-0">

                        <thead class="table-light">

                            <tr>

                                <th style="width:60px;">
                                    #
                                </th>

                                <th>
                                    Désignation
                                </th>

                                <th style="width:140px;">
                                    Quantité
                                </th>

                                <th>
                                    Description
                                </th>

                                <th style="width:170px;">
                                    Prix unitaire
                                </th>

                                <th style="width:170px;">
                                    Prix total
                                </th>

                            </tr>

                        </thead>


                        <tbody>

                            @foreach($summary['articles'] as $index => $article)

                                <tr>

                                    <td class="text-muted fw-semibold">
                                        {{ $index + 1 }}
                                    </td>


                                    <td>

                                        <div class="fw-semibold">

                                            {{ $article['designation'] ?? '' }}

                                        </div>

                                    </td>


                                    <td>

                                        @php
                                            $quantite = $article['quantite'] ?? 0;
                                        @endphp

                                        @if(
                                            strtoupper((string)$quantite) === 'NC' ||
                                            strtoupper((string)$quantite) === 'ND'
                                        )

                                            <span class="badge bg-warning text-dark">
                                                {{ $quantite }}
                                            </span>

                                        @else

                                            {{ $quantite }}

                                        @endif

                                    </td>


                                    <td>

                                        @if(!empty($article['description']))

                                            <span class="text-muted">

                                                {{ $article['description'] }}

                                            </span>

                                        @else

                                            <span class="text-muted">
                                                —
                                            </span>

                                        @endif

                                    </td>


                                    <td>

                                        @if(
                                            isset($article['prix_unitaire']) &&
                                            $article['prix_unitaire'] !== null &&
                                            $article['prix_unitaire'] !== ''
                                        )

                                            {{ $article['prix_unitaire'] }}

                                        @else

                                            <span class="text-muted">
                                                —
                                            </span>

                                        @endif

                                    </td>


                                    <td>

                                        @if(
                                            isset($article['prix_total']) &&
                                            $article['prix_total'] !== null &&
                                            $article['prix_total'] !== ''
                                        )

                                            {{ $article['prix_total'] }}

                                        @else

                                            <span class="text-muted">
                                                —
                                            </span>

                                        @endif

                                    </td>

                                </tr>

                            @endforeach

                        </tbody>

                    </table>

                </div>

            @else

                <div class="text-center py-5">

                    <i class="fa-solid fa-box-open text-muted fs-1 mb-3"></i>

                    <h6 class="fw-bold">
                        Aucun article détecté
                    </h6>

                    <p class="text-muted mb-0">
                        Aucun article exploitable n’a été trouvé
                        dans la feuille sélectionnée.
                    </p>

                </div>

            @endif

        </div>

    </div>


    {{-- =========================================================
         FOURNISSEURS
    ========================================================== --}}
    @if(!empty($summary['suppliers']))

        <div class="card shadow-sm border-0 mb-4">

            <div class="card-header bg-white border-0 pt-4 px-4">

                <div class="d-flex justify-content-between align-items-center">

                    <h5 class="fw-bold mb-0">

                        <i class="fa-solid fa-truck text-primary me-2"></i>

                        Fournisseurs

                    </h5>

                    <span class="badge bg-secondary fs-6">

                        {{ count($summary['suppliers']) }}

                    </span>

                </div>

            </div>


            <div class="card-body">

                <div class="row g-3">

                    @foreach($summary['suppliers'] as $supplier)

                        <div class="col-md-4">

                            <div class="border rounded p-3 h-100">

                                <div class="fw-semibold">

                                    {{ $supplier['name'] ?? $supplier['nom'] ?? $supplier }}

                                </div>

                                @if(!empty($supplier['matricule_fiscal']))

                                    <div class="small text-muted mt-1">

                                        Matricule fiscal :
                                        {{ $supplier['matricule_fiscal'] }}

                                    </div>

                                @endif

                                @if(!empty($supplier['email']))

                                    <div class="small text-muted">

                                        {{ $supplier['email'] }}

                                    </div>

                                @endif

                            </div>

                        </div>

                    @endforeach

                </div>

            </div>

        </div>

    @endif


    {{-- =========================================================
         OFFRES
    ========================================================== --}}
    @if(!empty($summary['offers']))

        <div class="card shadow-sm border-0 mb-4">

            <div class="card-header bg-white border-0 pt-4 px-4">

                <h5 class="fw-bold mb-1">

                    <i class="fa-solid fa-tags text-primary me-2"></i>

                    Offres détectées

                </h5>

                <p class="text-muted small mb-0">
                    Informations relatives aux offres des fournisseurs.
                </p>

            </div>


            <div class="card-body p-0">

                <div class="table-responsive">

                    <table class="table table-hover align-middle mb-0">

                        <thead class="table-light">

                            <tr>

                                <th>
                                    Fournisseur
                                </th>

                                <th>
                                    Article
                                </th>

                                 <th>
                                    prix HT
                                </th>

                                

                            </tr>

                        </thead>


                        <tbody>

                            @foreach($summary['offers'] as $offer)

                                <tr>

                                    <td>
                                        {{ $offer['fournisseur'] ?? $offer['supplier'] ?? '—' }}
                                    </td>

                                    <td>
                                        {{ $offer['article'] ?? $offer['designation'] ?? '—' }}
                                    </td>

                                    <td>

                                        @if(
                                            isset($offer['prix_unitaire_ht']) &&
                                            $offer['prix_unitaire_ht'] !== null
                                        )

                                            {{ $offer['prix_unitaire_ht'] }}

                                        @else

                                            <span class="text-muted">—</span>

                                        @endif

                                    </td>

                                    

                                </tr>

                            @endforeach

                        </tbody>

                    </table>

                </div>

            </div>

        </div>

    @endif


    {{-- =========================================================
         RÉSUMÉ
    ========================================================== --}}
    <div class="card shadow-sm border-0 mb-4">

        <div class="card-body p-4">

            <h5 class="fw-bold mb-4">

                <i class="fa-solid fa-chart-simple text-primary me-2"></i>

                Résumé de l’import

            </h5>


            <div class="row g-3">

                <div class="col-md-3">

                    <div class="border rounded p-3 text-center">

                        <div class="text-muted small">
                            Référence
                        </div>

                        <div class="fw-bold mt-1">

                            {{ $summary['reference'] ?? 'Non détectée' }}

                        </div>

                    </div>

                </div>


                <div class="col-md-3">

                    <div class="border rounded p-3 text-center">

                        <div class="text-muted small">
                            Articles
                        </div>

                        <div class="fw-bold fs-4 mt-1">

                            {{ count($summary['articles'] ?? []) }}

                        </div>

                    </div>

                </div>


                <div class="col-md-3">

                    <div class="border rounded p-3 text-center">

                        <div class="text-muted small">
                            Fournisseurs
                        </div>

                        <div class="fw-bold fs-4 mt-1">

                            {{ count($summary['suppliers'] ?? []) }}

                        </div>

                    </div>

                </div>


                <div class="col-md-3">

                    <div class="border rounded p-3 text-center">

                        <div class="text-muted small">
                            Offres
                        </div>

                        <div class="fw-bold fs-4 mt-1">

                            {{ count($summary['offers'] ?? []) }}

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>


    {{-- =========================================================
         CONFIRMATION
    ========================================================== --}}
    <div class="card shadow-sm border-0 mb-5">

        <div class="card-body p-4">

            <div class="d-flex align-items-start gap-3">

                <div class="text-success fs-3">

                    <i class="fa-solid fa-circle-check"></i>

                </div>

                <div class="flex-grow-1">

                    <h5 class="fw-bold mb-1">
                        Confirmer l’import ?
                    </h5>

                    <p class="text-muted mb-0">
                        Les données affichées ci-dessus seront enregistrées
                        dans le système.
                    </p>

                </div>

            </div>


            <hr class="my-4">


            <form method="POST"
                  action="{{ route('imports.confirm') }}">

                @csrf

                <div class="d-flex justify-content-end gap-2">

                    <a href="{{ route('imports.create') }}"
                       class="btn btn-outline-secondary">

                        <i class="fa-solid fa-xmark me-1"></i>

                        Annuler

                    </a>


                    <button type="submit"
                            class="btn btn-success px-4">

                        <i class="fa-solid fa-check me-1"></i>

                        Confirmer l’import

                    </button>

                </div>

            </form>

        </div>

    </div>

</div>

@endsection