<!DOCTYPE html>
<html lang="fr">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Connexion - FSG Achat</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <style>

        body{

            background:linear-gradient(135deg,#3f72d8,#6fa8ff);

            height:100vh;

            display:flex;

            justify-content:center;

            align-items:center;

            font-family:Arial,Helvetica,sans-serif;

        }

        .login-card{

            width:430px;

            background:white;

            border-radius:15px;

            box-shadow:0 15px 40px rgba(0,0,0,.25);

            padding:40px;

        }

        .logo{

            width:90px;

            margin:auto;

            display:block;

            margin-bottom:15px;

        }

        h2{

            text-align:center;

            font-weight:bold;

            margin-bottom:5px;

            color:#3f72d8;

        }

        .subtitle{

            text-align:center;

            color:gray;

            margin-bottom:30px;

        }

        .form-control{

            height:50px;

        }

        .btn-login{

            background:#3f72d8;

            color:white;

            height:50px;

            font-size:18px;

        }

        .btn-login:hover{

            background:#2f5fc0;

            color:white;

        }

    </style>

</head>

<body>

<div class="login-card">

    <img src="{{ asset('images/f.jpg') }}" class="logo">

    <h2>FSG ACHAT</h2>

    <p class="subtitle">

        Gestion des consultations et des procédures d'achat

    </p>

    @if($errors->any())

        <div class="alert alert-danger">

            @foreach($errors->all() as $error)

                <div>{{ $error }}</div>

            @endforeach

        </div>

    @endif

    <form method="POST" action="{{ route('login') }}">

        @csrf

        <div class="mb-3">

            <label class="form-label">

                <i class="fa-solid fa-envelope"></i>

                Adresse email

            </label>

            <input
                type="email"
                name="email"
                class="form-control"
                placeholder="Entrer votre email"
                required>

        </div>

        <div class="mb-4">

            <label class="form-label">

                <i class="fa-solid fa-lock"></i>

                Mot de passe

            </label>

            <input
                type="password"
                name="password"
                class="form-control"
                placeholder="Entrer votre mot de passe"
                required>

        </div>

        <button class="btn btn-login w-100">

    <i class="fa-solid fa-right-to-bracket"></i>

    Se connecter

</button>



    </form>

</div>

</body>

</html>