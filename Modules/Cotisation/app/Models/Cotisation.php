<?php

namespace Modules\Cotisation\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Cycle\Models\Tours;
use Modules\Members\Models\Members;

class Cotisation extends Model
{
    /**
     * The attributes that are mass assignable.
     */
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
        return $this->belongsTo(Members::class, 'membre_id');
    }

    public function paiements(){
        return $this->hasMany(Paiements::class);
    }

    public function penalite(){
        return $this->hasOne(Penalites::class);
    }

}
