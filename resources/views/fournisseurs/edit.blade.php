@extends('layouts.app')

@section('title', 'Modifier fournisseur')

@section('content')
<h1 class="h3 mb-4">Modifier : {{ $fournisseur->raison_sociale }}</h1>
<div class="card p-4" style="max-width: 600px;">
    <form method="POST" action="{{ route('fournisseurs.update', $fournisseur) }}">
        @method('PUT')
        @include('fournisseurs._form')
    </form>
</div>
@endsection
