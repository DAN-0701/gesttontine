<?php

namespace Modules\Cotisation\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Users;

class Penalites extends Model
{
    protected $fillable = [
        'membre_id',
        'cycle_id',
        'tour_id',
        'cotisation_id',
        'montant',
        'statut',
        'date_application',
        'date_paiement',
        'motif_annulation',
        'traite_par'
    ];

    public function membre()
    {
        return $this->belongsTo(\Modules\Members\Models\members::class, 'membre_id');
    }

    public function cycle()
    {
        return $this->belongsTo(\Modules\Cycle\Models\Cycle::class, 'cycle_id');
    }

    public function tour()
    {
        return $this->belongsTo(\Modules\Cycle\Models\Tours::class, 'tour_id');
    }

    public function cotisation()
    {
        return $this->belongsTo(Cotisation::class, 'cotisation_id');
    }

    public function traitePar()
    {
        return $this->belongsTo(Users::class, 'traite_par');
    }
}
