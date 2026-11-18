@extends('layouts.app')

@section('content')

<div class="container">

<div class="card">

<div class="card-header">

Choisir le format du PDF

</div>

<div class="card-body">

<form method="POST"
action="{{ route('documents.pdf',$consultation->id) }}">

@csrf

<div class="mb-3">

<label>

<input type="radio"
name="orientation"
value="Portrait"
checked>

Portrait

</label>

<br>

<label>

<input type="radio"
name="orientation"
value="Landscape">

Paysage

</label>

</div>

<button class="btn btn-primary">

Générer PDF

</button>

<a href="{{ route('documents.repartition',$consultation->id) }}"
class="btn btn-secondary">

Retour

</a>

</form>

</div>

</div>

</div>

@endsection