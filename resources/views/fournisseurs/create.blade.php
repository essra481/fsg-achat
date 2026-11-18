@extends('layouts.app')

@section('title', 'Nouveau fournisseur')

@section('content')
<h1 class="h3 mb-4">Nouveau fournisseur</h1>
<div class="card p-4" style="max-width: 600px;">
    <form method="POST" action="{{ route('fournisseurs.store') }}">
        @include('fournisseurs._form')
    </form>
</div>
@endsection
