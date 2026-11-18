@extends('layouts.app')

@section('title','Consultation')

@section('content')

<div class="container">

    <div class="d-flex justify-content-between mb-4">

        <h2>
            Consultation {{ $consultation->reference }}
        </h2>

        <div>

            <a href="{{ route('consultation.index') }}"
               class="btn btn-secondary">
                Retour
            </a>
            <a href="{{ route('consultation.pdf',$consultation->id) }}"
               class="btn btn-danger">
                 PDF
            </a>
            <a href="{{ route('consultation.exportExcel', $consultation->id) }}"
            class="btn btn-success">
                    Exporter Excel
            </a>

        </div>

    </div>


    @include('consultation.document')

</div>

@endsection