@extends('layouts.app')

@section('content')

<div class="container">

    <div class="card shadow">

        <div class="card-header bg-primary text-white">
            <h4 class="mb-0">Saisie des offres</h4>
        </div>

        <div class="card-body">

            <h5>Consultation : {{ $consultation->reference }}</h5>

            <p>
                <strong>Objet :</strong>
                {{ $consultation->objet }}
            </p>

            <div class="alert alert-info">
                <strong>Fournisseur :</strong>
                {{ $fournisseur->name }}
            </div>

            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @php
                $hasDescription = $consultation->articles->contains(function ($article) {
                    return !empty(trim($article->description ?? ''));
                });

                // Indexe les offres par article_id, quel que soit le format reçu du contrôleur
                $offresParArticle = collect($offres)->keyBy('article_id');
            @endphp

            <form
                method="POST"
                action="{{ route('offre.fournisseur.store', [
                    'consultation' => $consultation->id,
                    'fournisseur'  => $fournisseur->id
                ]) }}"
                id="offreForm"
                novalidate>

                @csrf

                <table class="table table-bordered table-striped text-center">

                    <thead class="table-dark">
                        <tr>
                            <th>#</th>
                            <th>Article</th>

                            @if($hasDescription)
                                <th>Description</th>
                            @endif

                            <th>Quantité</th>
                            <th>Prix unitaire HT</th>
                        </tr>
                    </thead>

                    <tbody>

                    @foreach($consultation->articles as $index => $article)

                        @php
                            $offre = $offresParArticle->get($article->id);

                            // Valeur à afficher : statut (ND/NC) s'il existe, sinon le prix
                            $valeurPrix = '';
                            if ($offre) {
                                $valeurPrix = !empty($offre->statut_prix)
                                    ? $offre->statut_prix
                                    : $offre->prix_unitaire_ht;
                            }
                        @endphp

                        <tr>
                            <td>{{ $index + 1 }}</td>

                            <td>
                                {{ $article->designation }}
                                <input
                                    type="hidden"
                                    name="article_id[]"
                                    value="{{ $article->id }}">
                            </td>

                            @if($hasDescription)
                                <td>{{ $article->description ?? '' }}</td>
                            @endif

                            <td>{{ $article->quantite }}</td>

                            <td>
                                <input
                                    type="text"
                                    name="prix_unitaire_ht[]"
                                    class="form-control text-center prix-ht @error('prix_unitaire_ht.' . $index) is-invalid @enderror"
                                    inputmode="decimal"
                                    value="{{ old('prix_unitaire_ht.' . $index, $valeurPrix) }}"
                                    placeholder="Prix HT ou ND ou NC"
                                    data-quantite="{{ $article->quantite }}"
                                    autocomplete="off"
                                    required>

                                <div class="text-danger small mt-1 erreur-prix" style="display:none;"></div>
                            </td>
                        </tr>

                    @endforeach

                    </tbody>

                </table>

                <div class="mt-4">
                    <a href="{{ route('offre.edit', $consultation->id) }}" class="btn btn-secondary">
                        Retour aux fournisseurs
                    </a>

                    <button type="submit" class="btn btn-success">
                        Enregistrer les offres
                    </button>
                </div>

            </form>

        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {

    const inputs = document.querySelectorAll('.prix-ht');
    const form = document.getElementById('offreForm');

    // Retourne un message d'erreur, ou '' si la valeur est valide
    function verifier(valeur, messageVide, messageInvalide) {
        valeur = valeur.trim();

        if (valeur === '') return messageVide;

        const majuscule = valeur.toUpperCase();
        if (majuscule === 'ND' || majuscule === 'NC') return '';

        if (!/^[0-9]+([.,][0-9]+)?$/.test(valeur)) return messageInvalide;

        return '';
    }

    function afficher(input, message) {
        const erreur = input.parentElement.querySelector('.erreur-prix');

        if (message) {
            erreur.textContent = message;
            erreur.style.display = 'block';
            input.classList.add('is-invalid');
        } else {
            erreur.style.display = 'none';
            input.classList.remove('is-invalid');
        }
    }

    inputs.forEach(function (input) {
        input.addEventListener('input', function () {
            // Pendant la saisie, un champ vide n'est pas une erreur
            const message = this.value.trim() === ''
                ? ''
                : verifier(this.value, '', 'Veuillez saisir un nombre valide, ND ou NC.');
            afficher(this, message);
        });
    });

    form.addEventListener('submit', function (event) {
        let formulaireValide = true;

        inputs.forEach(function (input) {
            const message = verifier(
                input.value,
                'Veuillez saisir un prix, ND ou NC.',
                'Veuillez saisir uniquement un nombre, ND ou NC.'
            );

            afficher(input, message);

            if (message) formulaireValide = false;
        });

        if (!formulaireValide) {
            event.preventDefault();
        }
    });

});
</script>

@endsection