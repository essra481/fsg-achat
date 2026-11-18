@extends('layouts.app')

@section('title', 'Tableau de bord')

@section('content')

<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold">Tableau de bord</h2>
            <p class="text-muted mb-0">
                Gestion des consultations et des procédures d'achat
            </p>
        </div>
    </div>

    <!-- Statistiques -->
    <div class="row mb-4">

        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card shadow border-0">
                <div class="card-body text-center">
                    <h6 class="text-muted">Demandes d'achat</h6>
                    <h2 class="fw-bold text-primary">{{ $nbDemandes }}</h2>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card shadow border-0">
                <div class="card-body text-center">
                    <h6 class="text-muted">Consultations</h6>
                    <h2 class="fw-bold text-success">{{ $nbConsultations }}</h2>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card shadow border-0">
                <div class="card-body text-center">
                    <h6 class="text-muted">Fournisseurs</h6>
                    <h2 class="fw-bold text-warning">{{ $nbFournisseurs }}</h2>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card shadow border-0">
                <div class="card-body text-center">
                    <h6 class="text-muted">Procès-verbaux</h6>
                    <h2 class="fw-bold text-danger">{{ $nbProcesVerbaux }}</h2>
                </div>
            </div>
        </div>

    </div>

    <!-- Dernières consultations -->
    <div class="card shadow border-0">

        <div class="card-header bg-primary text-white">
            Dernières consultations
        </div>

        <div class="card-body">

            <table class="table table-hover align-middle">

                <thead>
                    <tr>
                        <th>Référence</th>
                        <th>Objet</th>
                        <th>Date lancement</th>
                        <th>Date limite</th>
                    </tr>
                </thead>

                <tbody>

                @forelse($dernieresConsultations as $c)

                    <tr>

                        <td>{{ $c->reference }}</td>

                        <td>{{ $c->objet }}</td>

                        <td>{{ date('d/m/Y',strtotime($c->date_lancement)) }}</td>

                        <td>{{ date('d/m/Y',strtotime($c->date_limite)) }}</td>

                    </tr>

                @empty

                    <tr>

                        <td colspan="4" class="text-center text-muted">

                            Aucune consultation disponible.

                        </td>

                    </tr>

                @endforelse

                </tbody>

            </table>

        </div>

    </div>

</div>

@endsection