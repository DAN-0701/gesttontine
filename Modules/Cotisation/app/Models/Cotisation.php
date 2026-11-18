<?php

namespace Modules\Cotisation\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Cycle\Models\Tours;
use Modules\Members\Models\members;

class Cotisation extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'tour_id',
        'membre_id',
        'montant_attendu',
        'montant_paye',
        'statut'
    ];

    public function tour()
    {
        return $this->belongsTo(Tours::class, 'tour_id');
    }

    public function membre()
    {
        return $this->belongsTo(members::class, 'membre_id');
    }

    public function paiements()
    {
        return $this->hasMany(Paiements::class, 'cotisation_id');
    }

    public function penalite()
    {
        return $this->hasOne(Penalites::class, 'cotisation_id');
    }
}
