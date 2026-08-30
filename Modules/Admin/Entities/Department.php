<?php

namespace Modules\Admin\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Tasks\Entities\TaskModuleUserRole;

class Department extends Model
{
    use HasFactory, SoftDeletes;

    protected $primaryKey = 'department_id';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $date = 'deleted_at';
    protected $fillable = ['department_id', 'department_name', 'dept_code', 'status', 'user_id'];

    public function taskUserRoles()
    {
        return $this->hasMany(TaskModuleUserRole::class, 'department_id', 'department_id');
    }
    protected static function newFactory()
    {
        return \Modules\Admin\Database\factories\DepartmentFactory::new();
    }
}
