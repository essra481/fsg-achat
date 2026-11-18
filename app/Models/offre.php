<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class offre extends Model
{
    protected $table = 'offre';

    protected $fillable = [
    'consultation_id',
    'fournisseur_id',
    'article_id',
    'prix_unitaire_ht',
    'statut_prix',


    ];

    public function consultation()
    {
        return $this->belongsTo(consultation::class);
    }

    public function fournisseur()
    {
        return $this->belongsTo(fournisseurs::class);
    }

    public function article()
    {
        return $this->belongsTo(Article::class);
    }
}