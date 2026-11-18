<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Signataire extends Model
{
    protected $table = 'signataires';

    protected $fillable = [
        'nom',
        'fonction',
        'type',
        'actif',
        'date_debut',
        'date_fin',
    ];
   public function procesVerbaux()
{
    return $this->belongsToMany(
        \App\Models\procesverbal::class,
        'proces_verbal_signataire',
        'signataire_id',
        'proces_verbal_id'
    )
    ->withPivot('ordre');
}

    protected $casts = [
        'actif' => 'boolean',
        'date_debut' => 'date',
        'date_fin' => 'date',
    ];
}