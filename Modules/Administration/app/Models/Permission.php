<?php

namespace Modules\Administration\Models;

use App\Models\Users;
use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    protected $fillable = [
        'nom',
        'description',
    ];

    public function userPermissions()
    {
        return $this->hasMany(UserPermission::class, 'permission_id');
    }

    public function users()
    {
        return $this->belongsToMany(Users::class, 'user_permissions', 'permission_id', 'user_id')
                    ->withPivot('autorise')
                    ->withTimestamps();
    }
}
