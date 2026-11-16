<?php

namespace Modules\Cotisation\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Users;

class Paiements extends Model
{
    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'cotisation_id',
        'montant',
        'enregistre_par'
    ];
    public function cotisation(){
        return $this->belongsTo(Cotisation::class);}

    public function enregistrePar(){
        return $this->belongsTo(Users::class,'enregistre_par');
    }

}
