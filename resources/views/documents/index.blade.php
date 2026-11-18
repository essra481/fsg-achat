@extends('layouts.app')

@section('title','Gestion des documents')

@section('content')

<table class="table table-bordered">
    <thead>
        <tr>
            <th>Référence</th>
            <th>Objet</th>
            <th>Consultation</th>
            <th>Procès-verbal</th>
            <th>Répartition</th>
        </tr>
    </thead>

    <tbody>

    @foreach($consultations as $consultation)

    <tr>

        <td>{{$consultation->reference}}</td>

        <td>{{ $consultation->objet }}</td>

        <td>
            <a href="{{ route('consultations.show',$consultation->id) }}"
               class="btn btn-primary btn-sm">
               Ouvrir
            </a>
        </td>

        <td>
    @if($consultation->procesverbal)
        <a href="{{ route('procesverbal.show', $consultation->procesverbal->id) }}"
           class="btn btn-success btn-sm">
            Ouvrir
        </a>
    @else
        <span class="text-muted">
            Aucun PV
        </span>
    @endif
</td>

        <td>
            <a href="{{ route('documents.repartition',$consultation->id) }}"
               class="btn btn-warning btn-sm">
               Ouvrir
            </a>
        </td>

    </tr>

    @endforeach

    </tbody>

</table>

@endsection