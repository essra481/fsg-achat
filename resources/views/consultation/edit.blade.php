@extends('layouts.app')

@section('content')

<h1>Modifier consultation</h1>


<form action="{{ route('consultations.update',$consultation->id) }}" method="POST">

@csrf
@method('PUT')


<input type="text"
name="objet"
value="{{ $consultation->objet }}"
class="form-control">

<label> date de lancement </label>
<input type="date"
name="date_lancement"
value="{{ $consultation->date_lancement }}"
class="form-control">

<label> date limite  </label>
<input type="date"
name="date_limite"
value="{{ $consultation->date_limite }}"
class="form-control">


<button class="btn btn-success">
Modifier
</button>


</form>

@endsection