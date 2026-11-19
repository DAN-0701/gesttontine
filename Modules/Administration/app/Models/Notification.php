<?php

namespace Modules\Administration\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $table = 'notifications';

    protected $fillable = [
        'type',
        'titre',
        'message',
        'cycle_id',
        'tour_id',
        'lue',
    ];

    protected $casts = [
        'lue' => 'boolean',
    ];
}
