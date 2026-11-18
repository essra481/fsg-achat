@extends('layouts.app')

@section('content')

<div class="container">

    <div class="card shadow">


        <div class="card-header bg-primary text-white">

            <h4 class="mb-0">

                Sélection des fournisseurs

            </h4>

        </div>


        <div class="card-body">


            <h5>

                Consultation :
                {{ $consultation->reference }}

            </h5>


            <p>

                <strong>
                    Objet :
                </strong>

                {{ $consultation->objet }}

            </p>


            <hr>


            <input
                type="text"
                id="search"
                class="form-control mb-4"
                placeholder="Rechercher un fournisseur...">


            <form
                method="POST"
                action="{{ route(
                    'offre.fournisseurs.add',
                    $consultation->id
                ) }}">

                @csrf


                <table class="table table-bordered table-hover">


                    <thead class="table-dark">

                        <tr>

                            <th width="60">
                                #
                            </th>

                            <th>
                                Nom
                            </th>

                            <th>
                                Email
                            </th>

                            <th>
                                Téléphone
                            </th>

                        </tr>

                    </thead>


                    <tbody id="tableFournisseurs">


                    @foreach($fournisseurs as $fournisseur)


                        <tr>


                            <td>

                                <input
                                    type="checkbox"
                                    name="fournisseurs[]"
                                    value="{{ $fournisseur->id }}">

                            </td>


                            <td class="nom">

                                {{ $fournisseur->name }}

                            </td>


                            <td>

                                {{ $fournisseur->email }}

                            </td>


                            <td>

                                {{ $fournisseur->telephone }}

                            </td>


                        </tr>


                    @endforeach


                    </tbody>

                </table>


                <a
                    href="{{ route(
                        'offre.edit',
                        $consultation->id
                    ) }}"
                    class="btn btn-secondary">

                    Retour

                </a>


                <button
                    type="submit"
                    class="btn btn-success">

                    Ajouter les fournisseurs

                </button>


            </form>


        </div>

    </div>

</div>


<script>

document
    .getElementById('search')
    .addEventListener('keyup', function () {

        let recherche =
            this.value.toLowerCase();


        document
            .querySelectorAll('#tableFournisseurs tr')
            .forEach(function (ligne) {

                let nom =
                    ligne
                    .querySelector('.nom')
                    .textContent
                    .toLowerCase();


                if (nom.includes(recherche)) {

                    ligne.style.display = '';

                } else {

                    ligne.style.display = 'none';

                }

            });

    });

</script>

@endsection