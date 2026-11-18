<?php

namespace Modules\Cotisation\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Users;

class Penalites extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'cotisation_id',
        'montant',
        'statut',
        'date_application',
        'date_paiement',
        'motif_annulation',
        'traite_par'
    ];

    public function cotisation()
    {
        return $this->belongsTo(Cotisation::class, 'cotisation_id');
    }

    public function traitePar()
    {
        return $this->belongsTo(Users::class, 'traite_par');
    }
}
