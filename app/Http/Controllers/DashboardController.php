<?php

namespace App\Http\Controllers;

use App\Models\demande;
use App\Models\consultation;
use App\Models\fournisseurs;
use App\Models\procesverbal;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $nbDemandes = demande::count();
        $nbConsultations = consultation::count();
        $nbFournisseurs = fournisseurs::count();
        $nbProcesVerbaux = procesverbal::count();

        $dernieresConsultations = consultation::latest()->take(5)->get();

        return view('dashboard', compact(
            'nbDemandes',
            'nbConsultations',
            'nbFournisseurs',
            'nbProcesVerbaux',
            'dernieresConsultations'
        ));
    }
}