<?php

namespace App\Models;

//use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Modules\Admin\Entities\Permission;
use Modules\Admin\Entities\UserPermission;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;
    protected $primaryKey = 'user_id';
    protected $keyType = 'string';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = ['user_id', 'username', 'password', 'role_id', 'station_id', 'created_by', 'updated_by', 'status'];


    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function user()
    {
        return $this->hasOne(UserInfo::class, 'user_id', 'user_id');
    }

    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id', 'id');
    }

    public function station()
    {
        return $this->belongsTo(Station::class, 'station_id', 'station_id');
    }

    public function client()
    {
        return $this->belongsTo(Client::class, 'user_id', 'client_id');
    }

    public function permissions()
    {
        return $this->belongsToMany(
            \Modules\Admin\Entities\Permission::class,
            'user_permissions',
            'user_id',        // FK in pivot table referencing user
            'permission_id'   // FK in pivot table referencing permission
        );
    }

    public function hasPermission($key)
    {
        // Super admin logic if needed
        if ($this->role_id == 1) return true;

        return $this->permissions()->where('key', $key)->exists();
    }

    public function userpermissions()
    {
        return $this->hasMany(UserPermission::class, 'user_id', 'user_id');
    }
}
