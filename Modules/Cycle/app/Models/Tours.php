<?php

namespace Modules\Cycle\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Cotisation\Models\Cotisation;
use Modules\Members\Models\Members;

class Tours extends Model
{
    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'cycle_id',
        'beneficiaire_id',
        'numero',
        'date_prevue',
        'statut'
    ];
    public function cycle()
    {
        return $this->belongsTo(Cycle::class);
    }

    public function beneficiaire()
    {
        return $this->belongsTo(Members::class, 'beneficiaire_id');
    }

    public function cotisations()
    {
        return $this->hasMany(Cotisation::class, 'tour_id');
    }
}
