<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
    protected $table = 'article';

    protected $fillable = [
        'consultation_id',
        'designation',
        'description',
        'quantite',
    ];

    public function consultation()
    {
        return $this->belongsTo(consultation::class, 'consultation_id');
    }
    public function offres()
{
    return $this->hasMany(offre::class);
}
}