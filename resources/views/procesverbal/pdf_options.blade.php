@extends('layouts.app')

@section('content')

<div class="container mt-5">

    <div class="card shadow">

        <div class="card-header text-center">
            <h4>Génération du Procès-Verbal</h4>
        </div>

        <div class="card-body">

            <form action="{{ route('procesverbal.pdf', $procesverbal->id) }}"
                  method="POST"
                  target="_blank">

                @csrf

                <div class="mb-3">

                    <label class="form-label">
                        Choisir l'orientation
                    </label>

                    <select name="orientation" class="form-select">

                        <option value="portrait">
                            Portrait
                        </option>

                        <option value="landscape">
                            Paysage
                        </option>

                    </select>

                </div>

                <div class="d-flex justify-content-between">

                    <a href="{{ route('procesverbal.show', $procesverbal->id) }}"
                       class="btn btn-secondary">
                        ← Retour
                    </a>

                    <button type="submit" class="btn btn-danger">
                        Générer le PDF
                    </button>

                </div>

            </form>

        </div>

    </div>

</div>

@endsection