@extends('layouts.app')

@section('content')

<h1>Ajouter document</h1>


<form action="{{ route('document.store') }}"
method="POST">

@csrf


<input class="form-control mb-2"
type="text"
name="type_document"
placeholder="Type document">


<input class="form-control mb-2"
type="text"
name="chemin_fichier"
placeholder="Chemin fichier">


<input class="form-control mb-2"
type="date"
name="date_ajout">


<input class="form-control mb-2"
type="number"
name="fk_consultation_id"
placeholder="ID consultation">


<input class="form-control mb-2"
type="number"
name="fournisseur_id"
placeholder="ID fournisseur">


<input class="form-control mb-2"
type="number"
name="proces_verbal_id"
placeholder="ID procès verbal">


<button class="btn btn-success">
Enregistrer
</button>


</form>

@endsection