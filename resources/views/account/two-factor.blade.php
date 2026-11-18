@extends('layouts.app')

@section('title', 'Double authentification')

@section('content')

<div class="container-fluid">

    <div class="mb-4">
        <h3>
            <i class="fa-solid fa-shield-halved me-2"></i>
            Double authentification
        </h3>

        <p class="text-muted">
            Ajoute une seconde étape à la connexion : un code à 6 chiffres
            généré par une application (Google Authenticator, Microsoft
            Authenticator, Authy...).
        </p>

        <a href="{{ route('account.profile') }}" class="btn btn-sm btn-outline-secondary">
            &larr; Retour à Mon compte
        </a>
    </div>

    {{-- ============ Codes de secours (affichés une seule fois) ============ --}}
    @if($recoveryCodes)
        <div class="alert alert-warning">
            <strong>Conservez ces codes de secours dans un endroit sûr.</strong>
            Ils ne seront plus jamais affichés. Chaque code ne fonctionne
            qu'une seule fois et permet de vous connecter si vous perdez
            votre téléphone.

            <div class="row mt-3">
                @foreach($recoveryCodes as $code)
                    <div class="col-6 col-md-3 mb-2">
                        <code class="fs-6">{{ $code }}</code>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <div class="row">
        <div class="col-md-8">

            {{-- ============ ÉTAT 1 : désactivée, pas de secret ============ --}}
            @if(! $enabled && ! $pendingSecret)

                <div class="card">
                    <div class="card-header bg-primary text-white">
                        Activer la double authentification
                    </div>

                    <div class="card-body">
                        <form method="POST" action="{{ route('two-factor.enable') }}">
                            @csrf

                            <div class="mb-3">
                                <label class="form-label">
                                    Mot de passe actuel (pour confirmer)
                                </label>
                                <input type="password"
                                       name="current_password"
                                       class="form-control"
                                       autocomplete="current-password"
                                       required>
                                @error('current_password')
                                    <div class="text-danger mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <button type="submit" class="btn btn-primary">
                                Commencer l'activation
                            </button>
                        </form>
                    </div>
                </div>

            {{-- ============ ÉTAT 2 : secret généré, à confirmer ============ --}}
            @elseif(! $enabled && $pendingSecret)

                <div class="card">
                    <div class="card-header bg-warning">
                        Étape 2 : associer votre application
                    </div>

                    <div class="card-body">
                        <ol>
                            <li>
                                Dans votre application d'authentification, choisissez
                                <em>« Ajouter un compte » &rarr; « Saisir une clé de configuration »</em>.
                            </li>
                            <li>
                                Saisissez cette clé (type : basé sur l'heure) :
                                <div class="my-2">
                                    <code class="fs-5 user-select-all">{{ trim(chunk_split($pendingSecret, 4, ' ')) }}</code>
                                </div>
                            </li>
                            <li>Entrez ci-dessous le code à 6 chiffres affiché.</li>
                        </ol>

                        <form method="POST" action="{{ route('two-factor.confirm') }}" class="mt-3">
                            @csrf

                            <div class="mb-3">
                                <label class="form-label">Code à 6 chiffres</label>
                                <input type="text"
                                       name="code"
                                       class="form-control"
                                       style="max-width:200px"
                                       inputmode="numeric"
                                       autocomplete="one-time-code"
                                       maxlength="7"
                                       required>
                                @error('code')
                                    <div class="text-danger mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <button type="submit" class="btn btn-success">
                                Confirmer et activer
                            </button>
                        </form>

                        <form method="POST" action="{{ route('two-factor.cancel') }}" class="mt-2">
                            @csrf
                            <button type="submit" class="btn btn-link text-muted p-0">
                                Annuler
                            </button>
                        </form>
                    </div>
                </div>

            {{-- ============ ÉTAT 3 : activée ============ --}}
            @else

                <div class="card mb-4">
                    <div class="card-header bg-success text-white">
                        <i class="fa-solid fa-circle-check me-1"></i>
                        Double authentification activée
                    </div>

                    <div class="card-body">
                        <p class="mb-0">
                            Codes de secours restants :
                            <strong>{{ $remainingCodes }}</strong>
                        </p>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header">Générer de nouveaux codes de secours</div>

                    <div class="card-body">
                        <form method="POST" action="{{ route('two-factor.recovery') }}">
                            @csrf

                            <div class="row g-2">
                                <div class="col-md-6">
                                    <input type="password" name="current_password"
                                           class="form-control" placeholder="Mot de passe actuel"
                                           autocomplete="current-password" required>
                                </div>
                                <div class="col-md-4">
                                    <input type="text" name="code" class="form-control"
                                           placeholder="Code à 6 chiffres" inputmode="numeric" required>
                                </div>
                                <div class="col-md-2">
                                    <button class="btn btn-outline-primary w-100">OK</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header bg-danger text-white">
                        Désactiver la double authentification
                    </div>

                    <div class="card-body">
                        <form method="POST" action="{{ route('two-factor.disable') }}">
                            @csrf
                            @method('DELETE')

                            <div class="row g-2">
                                <div class="col-md-6">
                                    <input type="password" name="current_password"
                                           class="form-control" placeholder="Mot de passe actuel"
                                           autocomplete="current-password" required>
                                </div>
                                <div class="col-md-4">
                                    <input type="text" name="code" class="form-control"
                                           placeholder="Code à 6 chiffres" inputmode="numeric" required>
                                </div>
                                <div class="col-md-2">
                                    <button class="btn btn-outline-danger w-100">OK</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                @error('current_password')
                    <div class="text-danger mt-3">{{ $message }}</div>
                @enderror
                @error('code')
                    <div class="text-danger mt-1">{{ $message }}</div>
                @enderror

            @endif

        </div>
    </div>

</div>

@endsection
