<?php

namespace Modules\Cycle\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Cotisation\Models\Cotisation;
use Modules\Members\Models\members;

class Tours extends Model
{
    protected $fillable = [
        'cycle_id',
        'beneficiaire_id',
        'numero',
        'date_ouverture',
        'date_echeance',
        'date_prevue',
        'montant_distribue',
        'statut',
        'distribue_le',
        'cloture_le',
        'motif_cloture_forcee',
        'cloture_par',
    ];

    public function cycle()
    {
        return $this->belongsTo(Cycle::class, 'cycle_id');
    }

    public function beneficiaire()
    {
        return $this->belongsTo(members::class, 'beneficiaire_id');
    }

    public function cloturePar()
    {
        return $this->belongsTo(\App\Models\Users::class, 'cloture_par');
    }

    public function cotisations()
    {
        return $this->hasMany(Cotisation::class, 'tour_id');
    }
}
