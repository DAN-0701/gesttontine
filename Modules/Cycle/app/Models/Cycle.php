<?php

namespace Modules\Cycle\Models;

use App\Models\Users;
use Illuminate\Database\Eloquent\Model;
use Modules\Members\Models\members;

class cycle extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'libelle',
        'montant_cotisation',
        'periodicite',
        'date_debut',
        'date_fin',
        'delai_tolerance_jours',
        'montant_penalite',
        'frais_gestion',
        'statut',
        'createur_id'
    ];

    public function createur()
    {
        return $this->belongsTo(Users::class, 'createur_id');
    }

    public function membres()
    {
        return $this->belongsToMany(members::class, 'cycle_member', 'cycle_id', 'member_id')
                    ->withPivot('rang');
    }

    public function cycleMembers()
    {
        return $this->hasMany(CycleMembers::class, 'cycle_id');
    }

    public function tours()
    {
        return $this->hasMany(Tours::class, 'cycle_id');
    }
}
