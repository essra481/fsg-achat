<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class procesverbal extends Model
{
    protected $table = 'procesverbal';

    protected $fillable = [
        'date_creation',
        'consultation_id',
        'offre_retenue_id',
        'commentaire',
    ];

    public function consultation()
    {
        return $this->belongsTo(consultation::class, 'consultation_id');
    }

    public function offre()
    {
        return $this->belongsTo(offre::class, 'offre_retenue_id');
    }

    /**
     * Signataires associés à ce procès-verbal
     */
   public function signataires()
{
    return $this->belongsToMany(
        \App\Models\Signataire::class,
        'proces_verbal_signataire',
        'proces_verbal_id',
        'signataire_id'
    )
    ->withPivot('ordre')
    ->orderBy('proces_verbal_signataire.ordre');
}
}