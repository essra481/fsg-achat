@extends('layouts.app')

@section('content')

<div class="container">

    <div class="mb-4">

        <h2 class="fw-bold">
            Modifier le signataire
        </h2>

    </div>


    <div class="card shadow-sm border-0">

        <div class="card-body">

            <form method="POST"
                  action="{{ route('signataires.update', $signataire) }}">

                @csrf
                @method('PUT')

                <div class="row">

                    <div class="col-md-6 mb-3">

                        <label class="form-label">
                            Nom complet
                        </label>

                        <input type="text"
                               name="nom"
                               class="form-control"
                               value="{{ old('nom', $signataire->nom) }}"
                               required>

                    </div>


                    <div class="col-md-6 mb-3">

                        <label class="form-label">
                            Fonction
                        </label>

                        <input type="text"
                               name="fonction"
                               class="form-control"
                               value="{{ old('fonction', $signataire->fonction) }}"
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
        value="{{ old('type', $signataire->type) }}"
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
                               value="{{ old(
                                   'date_debut',
                                   $signataire->date_debut?->format('Y-m-d')
                               ) }}">

                    </div>


                   


                    <div class="col-md-12 mb-3">

                        <div class="form-check">

                            <input type="checkbox"
                                   name="actif"
                                   value="1"
                                   class="form-check-input"
                                   id="actif"
                                   @checked($signataire->actif)>

                            <label class="form-check-label"
                                   for="actif">

                                Signataire actif

                            </label>

                        </div>

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
                        Enregistrer les modifications

                    </button>

                </div>

            </form>

        </div>

    </div>

</div>

@endsection