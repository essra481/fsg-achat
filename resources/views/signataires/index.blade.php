@extends('layouts.app')

@section('content')

<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>
            <h2 class="fw-bold mb-1">
                Gestion des signataires
            </h2>

            <p class="text-muted mb-0">
                Gérez les personnes responsables des signatures.
            </p>
        </div>

        <a href="{{ route('signataires.create') }}"
           class="btn btn-primary">

            <i class="fas fa-plus me-2"></i>
            Ajouter un signataire

        </a>

    </div>


   


    <div class="card shadow-sm border-0">

        <div class="card-body p-0">

            <div class="table-responsive">

                <table class="table table-hover align-middle mb-0">

                    <thead class="table-light">

                        <tr>
                            <th>Nom</th>
                            <th>Fonction</th>
                            <th>Type</th>
                            <th>Date début</th>
                            <th>Date fin</th>
                            <th>Statut</th>
                            <th class="text-end">Actions</th>
                        </tr>

                    </thead>

                    <tbody>

                    @forelse($signataires as $signataire)

                        <tr>

                            <td class="fw-semibold">
                                {{ $signataire->nom }}
                            </td>

                            <td>
                                {{ $signataire->fonction }}
                            </td>

                            <td>
                                {{ $signataire->type }}
                            </td>

                            <td>
                                {{ $signataire->date_debut?->format('d/m/Y') ?? '-' }}
                            </td>

                            <td>
                                {{ $signataire->date_fin?->format('d/m/Y') ?? '-' }}
                            </td>

                            <td>

                                @if($signataire->actif)

                                    <span class="badge bg-success">
                                        Actif
                                    </span>

                                @else

                                    <span class="badge bg-secondary">
                                        Inactif
                                    </span>

                                @endif

                            </td>

                           <td class="text-end">

    {{-- Modifier --}}
    <a href="{{ route('signataires.edit', $signataire) }}"
       class="btn btn-sm btn-outline-primary">

        <i class="fas fa-edit"></i>
        Modifier

    </a>


    {{-- Désactiver --}}
    @if($signataire->actif)

        <form action="{{ route('signataires.destroy', $signataire) }}"
              method="POST"
              class="d-inline">

            @csrf
            @method('DELETE')

            <button type="submit"
                    class="btn btn-sm btn-outline-warning"
                    onclick="return confirm('Désactiver ce signataire ?')">

                <i class="fas fa-user-slash"></i>
                Désactiver

            </button>

        </form>

    @endif


    {{-- Supprimer définitivement --}}
    <form action="{{ route('signataires.forceDelete', $signataire) }}"
          method="POST"
          class="d-inline">

        @csrf
        @method('DELETE')

        <button type="submit"
                class="btn btn-sm btn-outline-danger"
                onclick="return confirm('Supprimer définitivement ce signataire ? Cette action est irréversible.')">

            <i class="fas fa-trash"></i>
            Supprimer

        </button>

    </form>

</td>

                                @if($signataire->actif)

                                    <form action="{{ route('signataires.destroy', $signataire) }}"
                                          method="POST"
                                          class="d-inline">

                                        @csrf
                                        @method('DELETE')

                                        <button type="submit"
                                                class="btn btn-sm btn-outline-danger"
                                                onclick="return confirm('Désactiver ce signataire ?')">

                                            <i class="fas fa-user-slash"></i>

                                        </button>

                                    </form>

                                @endif

                            </td>

                        </tr>

                    @empty

                        <tr>

                            <td colspan="7"
                                class="text-center py-5 text-muted">

                                Aucun signataire enregistré.

                            </td>

                        </tr>

                    @endforelse

                    </tbody>

                </table>

            </div>

        </div>

    </div>

</div>

@endsection