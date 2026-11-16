<?php

namespace Modules\Cycle\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Members\Models\members;

class CycleMembers extends Model
{
    protected $table = 'cycle_member';
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'cycle_id',
        'member_id',
        'rang'

    ];
    public function cycle(){
        return $this->belongsTo(Cycle::class);
    }

    public function member(){
        return $this->belongsTo(members::class);
    }

}
