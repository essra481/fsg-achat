<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Vérification en deux étapes</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body{
            min-height:100vh;
            display:flex;
            align-items:center;
            justify-content:center;
            background:linear-gradient(135deg,#0d6efd,#0a3d91);
        }
        .card-2fa{
            width:100%;
            max-width:430px;
            border:none;
            border-radius:14px;
            box-shadow:0 10px 30px rgba(0,0,0,.25);
        }
    </style>
</head>

<body>

<div class="card card-2fa p-4">

    <h4 class="text-center mb-2">Vérification en deux étapes</h4>

    <p class="text-muted text-center">
        Saisissez le code à 6 chiffres affiché par votre application
        d'authentification.
    </p>

    @if($errors->any())
        <div class="alert alert-danger">
            @foreach($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('two-factor.verify') }}">
        @csrf

        <div class="mb-3">
            <label class="form-label">Code de l'application</label>
            <input type="text"
                   name="code"
                   class="form-control text-center"
                   inputmode="numeric"
                   autocomplete="one-time-code"
                   maxlength="7"
                   placeholder="123456"
                   autofocus>
        </div>

        <details class="mb-3">
            <summary class="text-muted">
                J'ai perdu mon téléphone : utiliser un code de secours
            </summary>

            <div class="mt-2">
                <input type="text"
                       name="recovery_code"
                       class="form-control"
                       autocomplete="off"
                       placeholder="xxxxx-xxxxx">
            </div>
        </details>

        <button type="submit" class="btn btn-primary w-100">
            Vérifier
        </button>
    </form>

    <form method="POST" action="{{ route('logout') }}" class="mt-3 text-center">
        @csrf
        <button type="submit" class="btn btn-link text-muted">
            Annuler et retourner à la connexion
        </button>
    </form>

</div>

</body>
</html>
