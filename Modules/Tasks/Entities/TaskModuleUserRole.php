<?php

namespace Modules\Tasks\Entities;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Admin\Entities\Department;

class TaskModuleUserRole extends Model
{
    use HasFactory, SoftDeletes;

    protected $primaryKey = 'user_role_id';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $date = 'deleted_at';
    protected $fillable = ['user_id', 'user_role_id', 'role_id', 'department_id', 'assigned_by'];

    /** 🔹 Relationship to Role */
    public function role()
    {
        return $this->belongsTo(TaskModuleRole::class, 'role_id', 'id');
    }

    /** 🔹 Relationship to User */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    /** 🔹 Relationship to Department */
    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id', 'department_id');
    }

    /** 🔹 Relationship to the Assigning Admin */
    public function assignedBy()
    {
        return $this->belongsTo(User::class, 'assigned_by', 'user_id');
    }

    protected static function newFactory()
    {
        return \Modules\Tasks\Database\factories\TaskModuleUserRoleFactory::new();
    }
}
