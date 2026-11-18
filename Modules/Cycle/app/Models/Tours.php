<?php

namespace Modules\Cycle\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Cotisation\Models\Cotisation;
use Modules\Members\Models\members;

class Tours extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'cycle_id',
        'beneficiaire_id',
        'numero',
        'date_prevue',
        'statut'
    ];

    public function cycle()
    {
        return $this->belongsTo(Cycle::class, 'cycle_id');
    }

    public function beneficiaire()
    {
        return $this->belongsTo(members::class, 'beneficiaire_id');
    }

    public function cotisations()
    {
        return $this->hasMany(Cotisation::class, 'tour_id');
    }
}
