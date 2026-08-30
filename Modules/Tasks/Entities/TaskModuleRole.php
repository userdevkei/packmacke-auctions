<?php

namespace Modules\Tasks\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class TaskModuleRole extends Model
{
    use HasFactory, SoftDeletes;
    protected $table = 'task_module_roles';
    protected $date = 'deleted_at';
    protected $fillable = ['id', 'name'];

    public function userRoles()
    {
        return $this->hasMany(TaskModuleUserRole::class, 'role_id', 'id');
    }
    protected static function newFactory()
    {
        return \Modules\Tasks\Database\factories\TaskModuleRoleFactory::new();
    }
}
