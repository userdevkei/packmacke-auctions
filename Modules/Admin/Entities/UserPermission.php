<?php

namespace Modules\Admin\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class UserPermission extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'permission_id'];

    protected static function newFactory()
    {
        return \Modules\Admin\Database\factories\UserPermissionFactory::new();
    }
}
