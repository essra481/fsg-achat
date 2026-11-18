<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class fournisseurs extends Model
{
    protected $table  = 'fournisseurs';

    protected $fillable = [
        'id',
        'name',
        'matricule_fiscal',
        'email',
        'telephone',
        'created_at',
        'updated_at'
    ];
    public function offre()
    {
        return $this->hasMany(offre::class);
    }
    public function consultations()
{
    return $this->belongsToMany(
        consultation::class,
        'consultation_fournisseur',
        'fournisseurs_id',
        'consultation_id'
    );
}
}
