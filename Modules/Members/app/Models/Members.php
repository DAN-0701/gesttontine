<?php

namespace Modules\Members\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Cotisation\Models\Cotisation;
use Modules\Cycle\Models\cycle;
use Modules\Cycle\Models\CycleMembers;
use Modules\Cycle\Models\tours;

class members extends Model
{
    /**
     * The attributes that are mass assignable.
     */
    public $timestamps = false;
    protected $fillable = [
        'nom',
        'prenom',
        'email',
        'telephone',
        'adresse',
        'date_adhesion',
        'statut'

    ];
    public function cycle(){
        return $this->belongsToMany(cycle::class, 'cycle_member', 'member_id', 'cycle_id')->withPivot('rang');
    }
    public function cycleMembers(){
        return $this->hasMany(CycleMembers::class);
    }
    public function cotisation(){
        return $this->hasMany(Cotisation::class);
    }
    public function toursbeneficiaire(){
        return $this->hasMany(tours::class,'beneficiaire_id');
    }
}
