@extends('layouts.app')

@section('content')

<h1>Ajouter un procès-verbal</h1>

<form action="{{ route('procesverbal.store') }}" method="POST">

@csrf

<div class="mb-3">
<label>Date création</label>
<input type="date"
name="date_creation"
class="form-control">
</div>

<div class="mb-3">
<label>ID Consultation</label>
<input type="number"
name="pk_consultation_id"
class="form-control">
</div>

<div class="mb-3">
<label>ID Offre retenue</label>
<input type="number"
name="offre_retenue_id"
class="form-control">
</div>

<button class="btn btn-success">
Enregistrer
</button>

</form>

@endsection