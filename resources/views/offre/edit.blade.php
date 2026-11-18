@extends('layouts.app')

@section('content')

<div class="container">

    <div class="card shadow">

        <div class="card-header bg-primary text-white">

            <h4 class="mb-0">
                Gestion des offres
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


            


            <div class="d-flex justify-content-between align-items-center mb-3">

                <h5 class="mb-0">

                    Fournisseurs sélectionnés

                </h5>


                <a
                    href="{{ route(
                        'offre.fournisseurs.select',
                        $consultation->id
                    ) }}"
                    class="btn btn-success">

                    + Ajouter des fournisseurs

                </a>

            </div>


            @if($consultation->fournisseurs->count() > 0)


                <table class="table table-bordered table-striped text-center">

                    <thead class="table-dark">

                        <tr>

                            <th>
                                #
                            </th>

                            <th>
                                Fournisseur
                            </th>

                            <th>
                                Email
                            </th>

                            <th>
                                Téléphone
                            </th>

                            <th>
                                État des offres
                            </th>

                            <th>
                                Action
                            </th>

                        </tr>

                    </thead>


                    <tbody>


                    @foreach(
                        $consultation->fournisseurs
                        as $index => $fournisseur
                    )


                        @php

                            $nombreArticles =
                                $consultation->articles->count();

                            $nombreOffres =
                                \App\Models\Offre::where(
                                    'consultation_id',
                                    $consultation->id
                                )
                                ->where(
                                    'fournisseur_id',
                                    $fournisseur->id
                                )
                                ->count();

                        @endphp


                        <tr>


                            <td>

                                {{ $index + 1 }}

                            </td>


                            <td>

                                <strong>

                                    {{ $fournisseur->name }}

                                </strong>

                            </td>


                            <td>

                                {{ $fournisseur->email }}

                            </td>


                            <td>

                                {{ $fournisseur->telephone }}

                            </td>


                            <td>


                                @if($nombreArticles == 0)

                                    <span class="badge bg-secondary">

                                        Aucun article

                                    </span>


                                @elseif($nombreOffres == $nombreArticles)

                                    <span class="badge bg-success">

                                        Offres complètes

                                    </span>


                                @elseif($nombreOffres > 0)

                                    <span class="badge bg-warning text-dark">

                                        {{ $nombreOffres }}
                                        /
                                        {{ $nombreArticles }}

                                    </span>


                                @else

                                    <span class="badge bg-secondary">

                                        Pas encore saisies

                                    </span>

                                @endif


                            </td>


                            <td>

    <a
        href="{{ route(
            'offre.fournisseur.create',
            [
                'consultation' => $consultation->id,
                'fournisseur' => $fournisseur->id
            ]
        ) }}"
        class="btn btn-primary btn-sm">

        Ajouter / Modifier

    </a>


    <form
        method="POST"
        action="{{ route(
            'offre.fournisseur.remove',
            [
                'consultation' => $consultation->id,
                'fournisseur' => $fournisseur->id
            ]
        ) }}"
        style="display:inline-block;">

        @csrf

        @method('DELETE')


        <button
            type="submit"
            class="btn btn-danger btn-sm"
            onclick="return confirm(
                'Voulez-vous vraiment supprimer ce fournisseur de cette consultation ? Ses offres seront également supprimées.'
            );">

            Supprimer

        </button>

    </form>

</td>


                        </tr>


                    @endforeach


                    </tbody>

                </table>


                <div class="mt-4">

                    <a
                        href="{{ route(
                            'offre.show',
                            $consultation->id
                        ) }}"
                        class="btn btn-info text-white">

                        Voir toutes les offres

                    </a>

                </div>


            @else


                <div class="alert alert-warning text-center">

                    Aucun fournisseur n'a encore été sélectionné.

                    <br>

                    Cliquez sur
                    <strong>
                        Ajouter des fournisseurs
                    </strong>
                    pour commencer.

                </div>


            @endif


            <div class="mt-4">

                <a
                    href="{{ route('offre.index') }}"
                    class="btn btn-secondary">

                    Retour

                </a>

            </div>


        </div>

    </div>

</div>

@endsection