
@extends('layouts.app')

@section('title', 'Mon compte')

@section('content')

<div class="container-fluid">

    <div class="mb-4">
        <h2 class="fw-bold">
            <i class="fa-solid fa-user me-2"></i>
            Mon compte
        </h2>

        <p class="text-muted">
            Gérez vos informations personnelles et votre sécurité.
        </p>
    </div>


    <div class="row">

        <!-- ================= EMAIL ================= -->

        <div class="col-md-6 mb-4">

            <div class="card shadow border-0">

                <div class="card-header bg-primary text-white">
                    <i class="fa-solid fa-envelope me-2"></i>
                    Modifier mon email
                </div>

                <div class="card-body">

                    <form method="POST"
                          action="{{ route('account.email.update') }}">

                        @csrf

                        <div class="mb-3">

                            <label class="form-label">
                                Email actuel
                            </label>

                            <input type="email"
                                   class="form-control"
                                   value="{{ auth()->user()->email }}"
                                   disabled>

                        </div>


                        <div class="mb-3">

                            <label class="form-label">
                                Nouvel email
                            </label>

                            <input type="email"
                                   name="email"
                                   class="form-control"
                                   value="{{ old('email') }}"
                                   required>

                            @error('email')
                                <div class="text-danger mt-1">
                                    {{ $message }}
                                </div>
                            @enderror

                        </div>

                        <div class="mb-3">

                            <label class="form-label">
                                Mot de passe actuel (confirmation)
                            </label>

                            <input type="password"
                                   name="email_password"
                                   class="form-control"
                                   autocomplete="current-password"
                                   required>

                            @error('email_password')
                                <div class="text-danger mt-1">
                                    {{ $message }}
                                </div>
                            @enderror

                        </div>

                        @if(auth()->user()->hasTwoFactorEnabled())
                        <div class="mb-3">

                            <label class="form-label">
                                Code de double authentification
                            </label>

                            <input type="text"
                                   name="email_code"
                                   class="form-control"
                                   inputmode="numeric"
                                   autocomplete="one-time-code"
                                   required>

                            @error('email_code')
                                <div class="text-danger mt-1">
                                    {{ $message }}
                                </div>
                            @enderror

                        </div>
                        @endif


                        <button type="submit"
                                class="btn btn-primary">

                            <i class="fa-solid fa-save me-1"></i>
                            Modifier l'email

                        </button>

                    </form>

                </div>

            </div>

        </div>


        <!-- ================= PASSWORD ================= -->

        <div class="col-md-6 mb-4">

            <div class="card shadow border-0">

                <div class="card-header bg-warning">

                    <i class="fa-solid fa-lock me-2"></i>
                    Modifier mon mot de passe

                </div>

                <div class="card-body">

                    <form method="POST"
                          action="{{ route('account.password.update') }}">

                        @csrf


                        <div class="mb-3">

                            <label class="form-label">
                                Mot de passe actuel
                            </label>

                            <input type="password"
                                   name="current_password"
                                   class="form-control"
                                   required>

                            @error('current_password')
                                <div class="text-danger mt-1">
                                    {{ $message }}
                                </div>
                            @enderror

                        </div>


                        <div class="mb-3">

                            <label class="form-label">
                                Nouveau mot de passe
                            </label>

                            <input type="password"
                                   name="password"
                                   class="form-control"
                                   required>

                            @error('password')
                                <div class="text-danger mt-1">
                                    {{ $message }}
                                </div>

                            @enderror

                        </div>


                        <div class="mb-3">

                            <label class="form-label">
                                Confirmer le nouveau mot de passe
                            </label>

                            <input type="password"
                                   name="password_confirmation"
                                   class="form-control"
                                   required>

                        </div>

                        @if(auth()->user()->hasTwoFactorEnabled())
                        <div class="mb-3">

                            <label class="form-label">
                                Code de double authentification
                            </label>

                            <input type="text"
                                   name="password_code"
                                   class="form-control"
                                   inputmode="numeric"
                                   autocomplete="one-time-code"
                                   required>

                            @error('password_code')
                                <div class="text-danger mt-1">
                                    {{ $message }}
                                </div>
                            @enderror

                        </div>
                        @endif


                        <div class="alert alert-light border">

                            <small class="text-muted">

                                Le mot de passe doit contenir au minimum
                                <strong>12 caractères</strong>,
                                une majuscule, une minuscule,
                                un chiffre et un symbole.

                            </small>

                        </div>


                        <button type="submit"
                                class="btn btn-warning">

                            <i class="fa-solid fa-key me-1"></i>
                            Modifier le mot de passe

                        </button>

                    </form>

                </div>

            </div>

        </div>

    </div>

    <!-- ================= DOUBLE AUTHENTIFICATION ================= -->

    <div class="row">

        <div class="col-md-12 mb-4">

            <div class="card shadow border-0">

                <div class="card-header bg-dark text-white">
                    <i class="fa-solid fa-shield-halved me-2"></i>
                    Double authentification
                </div>

                <div class="card-body d-flex justify-content-between align-items-center">

                    <div>
                        @if(auth()->user()->hasTwoFactorEnabled())
                            <span class="badge bg-success">Activée</span>
                        @else
                            <span class="badge bg-secondary">Désactivée</span>
                        @endif

                        <span class="text-muted ms-2">
                            Protège votre compte avec un code à 6 chiffres
                            en plus du mot de passe.
                        </span>
                    </div>

                    <a href="{{ route('two-factor.show') }}"
                       class="btn btn-outline-dark">
                        Gérer
                    </a>

                </div>

            </div>

        </div>

    </div>

</div>

@endsection

