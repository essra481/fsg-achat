@extends('layouts.app')

@section('content')

<div class="container">

    <div class="mb-4">

        <h2 class="fw-bold">
            Ajouter un signataire
        </h2>

        <p class="text-muted">
            Ajouter une nouvelle personne responsable.
        </p>

    </div>


    <div class="card shadow-sm border-0">

        <div class="card-body">

            <form method="POST"
                  action="{{ route('signataires.store') }}">

                @csrf

                <div class="row">

                    <div class="col-md-6 mb-3">

                        <label class="form-label">
                            Nom complet
                        </label>

                        <input type="text"
                               name="nom"
                               class="form-control"
                               value="{{ old('nom') }}"
                               required>

                       

                    </div>


                    <div class="col-md-6 mb-3">

                        <label class="form-label">
                            Fonction
                        </label>

                        <input type="text"
                               name="fonction"
                               class="form-control"
                               value="{{ old('fonction') }}"
                               required>

                    </div>


                    <div class="col-md-6 mb-3">

    <label class="form-label">
        Type
    </label>

    <input
        type="text"
        name="type"
        class="form-control"
        value="{{ old('type') }}"
        placeholder="Exemple : Responsable achat"
        required
    >

</div>


                    <div class="col-md-3 mb-3">

                        <label class="form-label">
                            Date de début
                        </label>

                        <input type="date"
                               name="date_debut"
                               class="form-control"
                               value="{{ old('date_debut') }}">

                    </div>


                   
                </div>


                <div class="d-flex justify-content-end gap-2">

                    <a href="{{ route('signataires.index') }}"
                       class="btn btn-secondary">

                        Annuler

                    </a>

                    <button type="submit"
                            class="btn btn-primary">

                        <i class="fas fa-save me-2"></i>
                        Enregistrer

                    </button>

                </div>

            </form>

        </div>

    </div>

</div>

@endsection