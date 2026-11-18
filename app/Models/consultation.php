<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class consultation extends Model
{
    protected $table = 'consultation';

    protected $fillable = [
        'reference',
        'objet',
        'date_lancement',
        'date_limite',
        'demande_achat_id',
    ];

    public function demande()
    {
        return $this->belongsTo(demande::class, 'demande_id');
    }

    public function offres()
    {
        return $this->hasMany(offre::class);
    }

    public function documents()
    {
        return $this->hasMany(documents::class, 'fk_consultation_id');
    }

    public function procesverbal()
    {
        return $this->hasOne(procesverbal::class, 'consultation_id');
    }
    public function articles()
    {
        return $this->hasMany(Article::class, 'consultation_id');
    }
    public function fournisseurs()
{
    return $this->belongsToMany(fournisseurs::class,'consultation_fournisseur','consultation_id','fournisseurs_id'
    );
}
    
}