<?php

namespace Modules\Admin\Entities;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Permission extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['name', 'key'];

    public function users()
    {
        return $this->belongsToMany(
            \App\Models\User::class,
            'user_permissions',
            'permission_id',  // FK in pivot table referencing permission
            'user_id'         // FK in pivot table referencing user
        );
    }

    protected static function newFactory()
    {
        return \Modules\Admin\Database\factories\PermissionFactory::new();
    }
}
