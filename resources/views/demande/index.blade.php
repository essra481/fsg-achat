@extends('layouts.app')

@section('title', 'Demandes d\'achat')

@section('content')

<h1 class="h3 mb-4">Demandes d'achat</h1>

<a
    href="{{ route('demande.create') }}"
    class="btn btn-primary"
>
    Ajouter une demande
</a>

<table class="table mt-3">

    <thead>
        <tr>
            <th>Référence</th>
            <th>Objet</th>
            <th>Date</th>
            <th>Service</th>
            <th>Actions</th>
        </tr>
    </thead>

    <tbody>

    @foreach($demande as $d)

        <tr>

            <td>
                <strong>{{ $d->reference }}</strong>
            </td>

            <td>
                {{ $d->objet }}
            </td>

            <td>
                {{ $d->date_demande }}
            </td>

            <td>
                {{ $d->service_demandeur }}
            </td>

            <td>

                <a
                    href="{{ route('demande.edit', $d->id) }}"
                    class="btn btn-warning btn-sm"
                >
                    Modifier
                </a>

                <form
                    action="{{ route('demande.destroy', $d->id) }}"
                    method="POST"
                    style="display:inline"
                >

                    @csrf
                    @method('DELETE')

                    <button class="btn btn-danger btn-sm">
                        Supprimer
                    </button>

                </form>

            </td>

        </tr>

    @endforeach

    </tbody>

</table>

@endsection