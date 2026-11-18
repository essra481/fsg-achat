<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class documents extends Model
{
    protected $table  = 'documents';

    protected $fillable = [
        
        'type_document',
        'chemin_fichier',
        'date_ajout',
        'fk_consultation_id',
        'fournisseur_id',
        'proces_verbal_id'
    ];
    public function consultation()
    {
        return $this->belongsto(consultation::class,'fk_consultation_id');
    }
    public function procesverbal()
    {
        return $this->belongsto(procesverbal::class,'proces_verbal_id');
    }
    public function fournisseur()
    {
        return $this->belongsTo(Fournisseur::class, 'fournisseur_id');
    }
}
