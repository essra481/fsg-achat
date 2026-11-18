@extends('layouts.app')

@section('content')

<div class="container">

    <h1 class="mb-4">Modifier le procès-verbal</h1>

    <form
        action="{{ route('procesverbal.update', $procesverbal->id) }}"
        method="POST"
    >

        @csrf
        @method('PUT')


        {{-- ========================================================= --}}
        {{-- INFORMATIONS DU PV                                       --}}
        {{-- ========================================================= --}}

        <div class="card shadow-sm border-0 mb-4">

            <div class="card-header">
                <h5 class="mb-0">
                    Informations du procès-verbal
                </h5>
            </div>

            


        {{-- ========================================================= --}}
        {{-- SIGNATAIRES                                               --}}
        {{-- ========================================================= --}}

        <div class="card shadow-sm border-0 mb-4">

            <div class="card-header bg-primary text-white">

                <h5 class="mb-0">
                    <i class="fas fa-users me-2"></i>
                    Signataires du procès-verbal
                </h5>

            </div>


            <div class="card-body">

                @if($signataires->count() > 0)

                    @php
                        $signatairesParType = $signataires->groupBy('type');
                    @endphp


                    @foreach($signatairesParType as $type => $listeSignataires)

                        <div class="mb-4">

                            <h6 class="fw-bold border-bottom pb-2">
                                {{ $type ?: 'Autre' }}
                            </h6>


                            <div class="row">

                                @foreach($listeSignataires as $signataire)

                                    <div class="col-md-6 col-lg-4 mb-3">

                                        <div class="form-check">

                                            <input
                                                class="form-check-input"
                                                type="checkbox"
                                                name="signataires[]"
                                                value="{{ $signataire->id }}"
                                                id="signataire_{{ $signataire->id }}"

                                                @checked(
                                                    in_array(
                                                        $signataire->id,
                                                        old(
                                                            'signataires',
                                                            $selectedSignataires
                                                        )
                                                    )
                                                )
                                            >

                                            <label
                                                class="form-check-label"
                                                for="signataire_{{ $signataire->id }}"
                                            >

                                                <strong>
                                                    {{ $signataire->nom }}
                                                </strong>

                                                @if($signataire->fonction)

                                                    <br>

                                                    <small class="text-muted">
                                                        {{ $signataire->fonction }}
                                                    </small>

                                                @endif

                                            </label>

                                        </div>

                                    </div>

                                @endforeach

                            </div>

                        </div>

                    @endforeach

                @else

                    <div class="alert alert-warning mb-0">

                        <i class="fas fa-exclamation-triangle me-2"></i>

                        Aucun signataire actif n'est disponible.

                    </div>

                @endif

            </div>

        </div>


        {{-- ========================================================= --}}
        {{-- BOUTONS                                                   --}}
        {{-- ========================================================= --}}

        <div class="d-flex gap-2">

            <button
                type="submit"
                class="btn btn-success"
            >

                <i class="fas fa-save me-2"></i>

                Modifier

            </button>


            <a
                href="{{ route(
                    'procesverbal.show',
                    $procesverbal->id
                ) }}"
                class="btn btn-secondary"
            >

                <i class="fas fa-arrow-left me-2"></i>

                Annuler

            </a>

        </div>

    </form>

</div>

@endsection