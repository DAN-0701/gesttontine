<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Modules\Cotisation\Models\Paiements;
use Modules\Cotisation\Models\Penalites;
use Modules\Cycle\Models\Cycle;

class Users extends Authenticatable
{
    use HasFactory;

    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    public function cycles(){
        return $this->hasMany(Cycle::class,'createur_id');
    }
    public function paiements(){
        return $this->hasMany(Paiements::class,'enregistre_par');
    }

    public function penalites(){
        return $this->hasMany(Penalites::class,'traite_par');
    }

}
