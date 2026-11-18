@extends('layouts.app')

@section('content')

<div class="container mt-4">

    <h2 class="mb-4">
        Gestion des offres
    </h2>


    


    <table class="table table-bordered table-striped text-center">

        <thead class="table-dark">

            <tr>
                <th>Référence consultation</th>
                <th>Objet</th>
                <th>Date lancement</th>
                <th>Action</th>
            </tr>

        </thead>


        <tbody>

        @foreach($consultation as $consultation)

            <tr>

                <td>
                    {{ $consultation->reference }}
                </td>


                <td>
                    {{ $consultation->objet }}
                </td>


                <td>
                    {{ $consultation->date_lancement }}
                </td>


                <td>

                    <a href="{{ route('offre.edit',$consultation->id) }}"
                       class="btn btn-primary">

                        Modifier offre

                    </a>

                </td>

            </tr>

        @endforeach


        </tbody>

    </table>


</div>

@endsection