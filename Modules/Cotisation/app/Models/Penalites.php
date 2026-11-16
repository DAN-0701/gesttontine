<?php

namespace Modules\Cotisation\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Users;

class Penalites extends Model
{
    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'cotisation_id',
        'montant',
        'statut',
        'traite_par'
    ];
    public function cotisation(){
        return $this->belongsTo(Cotisation::class);
    }

    public function traitePar(){
        return $this->belongsTo(Users::class,'traite_par');
    }

}
