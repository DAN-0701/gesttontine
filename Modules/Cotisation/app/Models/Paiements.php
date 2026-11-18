<?php

namespace Modules\Cotisation\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Users;

class Paiements extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'cotisation_id',
        'montant',
        'date_paiement',
        'mode',
        'reference',
        'annule',
        'motif_annulation',
        'enregistre_par'
    ];

    public function cotisation()
    {
        return $this->belongsTo(Cotisation::class, 'cotisation_id');
    }

    public function enregistrePar()
    {
        return $this->belongsTo(Users::class, 'enregistre_par');
    }
}
