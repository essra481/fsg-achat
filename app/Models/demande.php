<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class demande extends Model
{
    protected $table = 'demande';

    protected $fillable = [
        'reference',
        'objet',
        'date_demande',
        'service_demandeur',
    ];

    public function consultation()
    {
        return $this->hasOne(consultation::class);
    }
}