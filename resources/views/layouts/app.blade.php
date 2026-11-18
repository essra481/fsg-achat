<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title','Gestion des achats')</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <style>

        *{
            margin:0;
            padding:0;
            box-sizing:border-box;
        }

        body{
            background:#f5f7fb;
            font-family:Arial, Helvetica, sans-serif;
        }

        /* ================= Sidebar ================= */

        .sidebar{
            position:fixed;
            left:0;
            top:0;
            width:260px;
            height:100vh;
            background:#3f72d8;
            color:#fff;
        }

        .logo{
            text-align:center;
            padding:25px 15px;
            font-size:22px;
            font-weight:bold;
            border-bottom:1px solid rgba(255,255,255,.2);
        }

        .menu{
            margin-top:20px;
        }

        .menu a{
            display:flex;
            align-items:center;
            gap:15px;
            color:white;
            text-decoration:none;
            padding:15px 25px;
            transition:.3s;
        }

        .menu a:hover{
            background:rgba(255,255,255,.15);
            padding-left:35px;
        }

        .menu a.active{
            background:rgba(255,255,255,.20);
            border-left:5px solid white;
        }

        .menu i{
            width:25px;
        }

        /* ================= Contenu ================= */

        .content{
            margin-left:260px;
            min-height:100vh;
        }

        .navbar-custom{
            background:white;
            padding:18px 30px;
            box-shadow:0 2px 8px rgba(0,0,0,.08);
            display:flex;
            justify-content:space-between;
            align-items:center;
        }

        .page{
            padding:30px;
        }

        .card{
            border:none;
            border-radius:12px;
            box-shadow:0 3px 10px rgba(0,0,0,.08);
        }

    </style>

</head>

<body>

<!-- ================= Sidebar ================= -->

<div class="sidebar">

    <div class="logo">
        <i class="fa-solid fa-cart-shopping"></i><br>
        FSG ACHAT
    </div>

    <div class="menu">

        <a href="{{ route('dashboard') }}"
           class="{{ request()->routeIs('dashboard') ? 'active' : '' }}">
            <i class="fa-solid fa-house"></i>
            Tableau de bord
        </a>

        <a href="{{ route('demande.index') }}"
           class="{{ request()->routeIs('demande.*') ? 'active' : '' }}">
            <i class="fa-solid fa-file-circle-plus"></i>
            Demandes d'achat
        </a>

        <a href="{{ route('consultations.index') }}"
           class="{{ request()->routeIs('consultations.*') ? 'active' : '' }}">
            <i class="fa-solid fa-folder-open"></i>
            Consultations
        </a>
       

        <a href="{{ route('fournisseurs.index') }}"
           class="{{ request()->routeIs('fournisseurs.*') ? 'active' : '' }}">
            <i class="fa-solid fa-building"></i>
            Fournisseurs
        </a>

        <a href="{{ route('offre.index') }}"
           class="{{ request()->routeIs('offre.*') ? 'active' : '' }}">
            <i class="fa-solid fa-file-invoice-dollar"></i>
            Offres
        </a>

        <a href="{{ route('procesverbal.index') }}"
           class="{{ request()->routeIs('procesverbal.*') ? 'active' : '' }}">
            <i class="fa-solid fa-clipboard-check"></i>
            Procès-verbaux
        </a>
        <a href="{{ route('document.index') }}"
           class="{{ request()->routeIs('document.*') ? 'active' : '' }}">
            <i class="fa-solid fa-folder"></i>
            Documents
        </a>
         <a href="{{ route('imports.create') }}"
           class="{{ request()->routeIs('imports.*') ? 'active' : '' }}">
            <i class="fa-solid fa-file-import"></i>
            Import Excel
        </a>
                
        <a href="{{ route('account.profile') }}"
           class="{{ request()->routeIs('account.*') ? 'active' : '' }}">
            <i class="fa-solid fa-user"></i>
            Mon compte
        </a>
        <li class="nav-item">

    <a href="{{ route('signataires.index') }}"
       class="nav-link">

        <i class="fas fa-signature"></i>

        
            Signataires
        

    </a>

</li>

        <form method="POST" action="{{ route('logout') }}" class="m-0">
            @csrf
            <button type="submit"
                    style="display:block;width:100%;text-align:left;background:none;border:0;color:white;padding:15px 25px;font-size:inherit;cursor:pointer;">
                <i class="fa-solid fa-right-from-bracket" style="width:25px;"></i>
                Déconnexion
            </button>
        </form>

    </div>

</div>

<!-- ================= Contenu ================= -->

<div class="content">

    <div class="navbar-custom">

        <h4 class="mb-0">
            Gestion des consultations et des procédures d'achat
        </h4>

        <span class="badge bg-primary p-2">
            Faculté des Sciences de Gabès
        </span>

    </div>

    <div class="page">

        @if(session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger">
                {{ session('error') }}
            </div>
        @endif

        @yield('content')

    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
