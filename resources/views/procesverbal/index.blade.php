@extends('layouts.app')

@section('content')

<div class="container">

    <h2 class="mb-4">
        Gestion des Procès-Verbaux
    </h2>

    <table class="table table-bordered table-striped text-center">

        <thead class="table-dark">

            <tr>
                <th>Référence</th>
                <th>Objet</th>
                <th>Date consultation</th>
                <th>Action</th>
            </tr>

        </thead>

        <tbody>

        @foreach($consultations as $consultation)

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

                @if($consultation->procesverbal)

                    {{-- Voir le PV --}}
                    <a
                        href="{{ route('procesverbal.show', $consultation->procesverbal->id) }}"
                        class="btn btn-success"
                    >
                        <i class="fas fa-eye me-1"></i>
                        Voir le PV
                    </a>

                    {{-- Modifier le PV --}}
                    <a
                        href="{{ route('procesverbal.edit', $consultation->procesverbal->id) }}"
                        class="btn btn-warning"
                    >
                        <i class="fas fa-edit me-1"></i>
                        Modifier
                    </a>

                @else

                    <form
                        action="{{ route('procesverbal.store') }}"
                        method="POST"
                        class="d-inline"
                    >

                        @csrf

                        <input
                            type="hidden"
                            name="consultation_id"
                            value="{{ $consultation->id }}"
                        >

                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-plus me-1"></i>
                            Créer le PV
                        </button>

                    </form>

                @endif

            </td>

        </tr>

        @endforeach

        </tbody>

    </table>

</div>

@endsection