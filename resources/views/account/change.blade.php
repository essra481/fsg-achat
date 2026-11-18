<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Configurer mon compte</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>

<div class="container">

    <div class="row justify-content-center mt-5">

        <div class="col-md-6">

            <div class="card shadow">

                <div class="card-body p-4">

                    <h3 class="text-center mb-3">
                        Première connexion
                    </h3>

                    <p class="text-muted text-center">
                        Pour des raisons de sécurité, vous devez modifier
                        votre adresse email et votre mot de passe avant
                        d'accéder à l'application.
                    </p>

                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('account.update') }}">

                        @csrf

                        <div class="mb-3">

                            <label class="form-label">
                                Nouvelle adresse email
                            </label>

                            <input
                                type="email"
                                name="email"
                                class="form-control"
                                value="{{ old('email', auth()->user()->email) }}"
                                required
                            >

                        </div>

                        <div class="mb-3">

                            <label class="form-label">
                                Nouveau mot de passe
                            </label>

                            <input
                                type="password"
                                name="password"
                                class="form-control"
                                required
                            >

                            <small class="text-muted">
                                Minimum 12 caractères avec majuscules,
                                minuscules, chiffres et symbole.
                            </small>

                        </div>

                        <div class="mb-3">

                            <label class="form-label">
                                Confirmer le nouveau mot de passe
                            </label>

                            <input
                                type="password"
                                name="password_confirmation"
                                class="form-control"
                                required
                            >

                        </div>

                        <button type="submit" class="btn btn-primary w-100">
                            Enregistrer mes informations
                        </button>

                    </form>

                </div>

            </div>

        </div>

    </div>

</div>

</body>

</html>