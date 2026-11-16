<?php

namespace Modules\Cycle\Models;

use App\Models\Users;
use Illuminate\Database\Eloquent\Model;
use Modules\Members\Models\members;

class cycle extends Model
{
    /**
     * The attributes that are mass assignable.
     */
    public $timestamps = false;
    protected $fillable = [
        'libelle',
        'date_debut',
        'date_fin',
        'statut',
        'createur_id'
    ];
    public function createur(){
        return $this->belongsTo(Users::class,'createur_id');
    }
    public function membres() {
        return $this->belongsToMany( members::class)
                    ->withPivot('rang');
    }
    public function cycleMembers(){
        return $this->hasMany(CycleMembers::class);
    }
    public function tours(){
        return $this->hasMany(tours::class);
    }

}
