@extends('layouts.app')

@section('title', 'Fournisseurs')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3">Fournisseurs</h1>
    <a href="{{ route('fournisseurs.create') }}" class="btn btn-primary">Nouveau fournisseur</a>
</div>

<form method="GET" class="mb-3">
    <input type="text" name="q" class="form-control" style="max-width: 320px;" placeholder="Rechercher par nom ou matricule fiscal" value="{{ request('q') }}">
</form>

<div class="card p-3">
    <table class="table table-hover align-middle">
        <thead><tr><th>Name</th><th>Matricule fiscal</th><th>Telephone</th><th>Email</th><th></th></tr></thead>
        <tbody>
        @forelse($fournisseurs as $f)
            <tr>
                <td>{{ $f->name }}</td>
                <td>{{ $f->matricule_fiscal }}</td>
                <td>{{ $f->telephone ?? '—' }}</td>
                <td>{{ $f->email ?? '—' }}</td>
                <td>
                    <a href="{{ route('fournisseurs.edit', $f) }}" class="small">Modifier</a>

                    <form action="{{ route('fournisseurs.destroy', $f) }}"
                          method="POST"
                          style="display:inline;"
                          onsubmit="return confirm('Voulez-vous vraiment supprimer ce fournisseur ?');">
                        @csrf
                        @method('DELETE')

                        <button type="submit"
                                class="btn btn-link btn-sm text-danger p-0 ms-2">
                            Supprimer
                        </button>
                    </form>
                </td>
            </tr>
        @empty
            <tr><td colspan="5" class="text-center text-muted py-4">Aucun fournisseur enregistre.</td></tr>
        @endforelse
        </tbody>
    </table>

    <div class="d-flex justify-content-between align-items-center mt-3">
        <div class="text-muted small">
            Showing {{ $fournisseurs->firstItem() }} to {{ $fournisseurs->lastItem() }}
            of {{ $fournisseurs->total() }} results
        </div>

        <div class="small-pagination">
            {{ $fournisseurs->onEachSide(1)->links('pagination::bootstrap-5') }}
        </div>
    </div>
</div>
@endsection