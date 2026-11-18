@extends('layouts.app')

@section('title', $fournisseur->name)

@section('content')

<div class="d-flex justify-content-between align-items-start mb-4">

    <div>
        <h1 class="h3 mb-1">
            {{ $fournisseur->name }}
        </h1>

        <p class="text-muted mb-0">
            Informations du fournisseur
        </p>
    </div>

    <a href="{{ route('fournisseurs.edit', $fournisseur) }}"
       class="btn btn-outline-secondary btn-sm">
        Modifier
    </a>

</div>


{{-- Informations du fournisseur --}}
<div class="card shadow-sm border-0 p-4 mb-4">

    <h2 class="h6 mb-4">
        Informations du fournisseur
    </h2>

    <dl class="row mb-0">

        <dt class="col-sm-3">
            Nom
        </dt>

        <dd class="col-sm-9">
            {{ $fournisseur->name }}
        </dd>


        <dt class="col-sm-3">
            Matricule fiscal
        </dt>

        <dd class="col-sm-9">
            {{ $fournisseur->matricule_fiscal }}
        </dd>


        <dt class="col-sm-3">
            Téléphone
        </dt>

        <dd class="col-sm-9">
            {{ $fournisseur->telephone ?? '—' }}
        </dd>


        <dt class="col-sm-3">
            Email
        </dt>

        <dd class="col-sm-9">
            {{ $fournisseur->email ?? '—' }}
        </dd>

    </dl>

</div>


{{-- Historique des consultations --}}
<div class="card shadow-sm border-0 p-4 mb-4">

    <div class="d-flex justify-content-between align-items-center mb-3">

        <h2 class="h6 mb-0">
            Historique des consultations
        </h2>

    </div>


    <div class="table-responsive">

        <table class="table table-sm align-middle mb-0">

            <thead>
                <tr>
                    <th>Consultation</th>
                    <th>Référence</th>
                    <th>Objet</th>
                </tr>
            </thead>

            <tbody>

                @forelse($fournisseur->consultations as $consultation)

                    <tr>

                        <td>
                            Consultation
                        </td>

                        <td>
                            <a href="{{ route('consultation.show', $consultation) }}">
                                {{ $consultation->reference }}
                            </a>
                        </td>

                        <td>
                            {{ $consultation->objet }}
                        </td>

                    </tr>

                @empty

                    <tr>

                        <td colspan="3"
                            class="text-center text-muted py-4">

                            Aucune participation à une consultation.

                        </td>

                    </tr>

                @endforelse

            </tbody>

        </table>

    </div>

</div>


{{-- Retour --}}
<a href="{{ route('fournisseurs.index') }}"
   class="btn btn-link mt-2">

    ← Retour à la liste

</a>

@endsection