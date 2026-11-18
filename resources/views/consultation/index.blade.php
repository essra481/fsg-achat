@extends('layouts.app')

@section('title','Liste des consultations')

@section('content')

<div class="container">

    <div class="d-flex justify-content-between align-items-center mb-4">

        <h2 class="fw-bold">
            Liste des consultations
        </h2>

    </div>

    <div class="card shadow">

        <div class="card-body">

            <table class="table table-bordered table-hover align-middle text-center">

                <thead class="table-dark">

                <tr>

                    <th>Référence</th>

                    <th>Objet</th>

                    <th>Date lancement</th>

                    <th>Date limite</th>

                

                    <th width="320">
                        Actions
                    </th>

                </tr>

                </thead>

                <tbody>

                @forelse($consultation as $c)

                    <tr>

                        <td>

                            {{ $c->reference }}

                        </td>

                        <td>

                            {{ $c->objet }}

                        </td>

                        <td>

                            {{ $c->date_lancement }}

                        </td>

                        <td>

                            {{ $c->date_limite }}

                        </td>



                        <td>

                            <a href="{{ route('consultation.show',$c->id) }}"
                               class="btn btn-info btn-sm">

                                Voir

                            </a>

                            <a href="{{ route('consultation.edit',$c->id) }}"
                               class="btn btn-warning btn-sm">

                                Modifier

                            </a>

                            <a href="{{ route('consultation.pdf',$c->id) }}"
                               class="btn btn-danger btn-sm">

                                PDF

                            </a>
                           <a href="{{ route('consultation.exportExcel', $c->id) }}"
                            class="btn btn-success">
                                Exporter Excel
                            </a>

                             <form action="{{ route('consultation.destroy',$c->id) }}"
                                  method="POST"
                                  style="display:inline;">

                                @csrf
                                @method('DELETE')

                                <button
                                    type="submit"
                                    class="btn btn-outline-danger btn-sm"
                                    onclick="return confirm('Supprimer cette consultation ?')">

                                    Supprimer

                                </button>

                            </form>

                        </td>

                    </tr>

                @empty

                    <tr>

                        <td colspan="6">

                            Aucune consultation trouvée.

                        </td>

                    </tr>

                @endforelse

                </tbody>

            </table>

        </div>

    </div>

</div>

@endsection
