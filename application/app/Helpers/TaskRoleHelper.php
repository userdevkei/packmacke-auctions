<?php
namespace App\Helpers;


use Modules\Tasks\Entities\TaskModuleUserRole;

class TaskRoleHelper
{
    public static function role($userId)
    {
        return TaskModuleUserRole::with('role')->where('user_id', $userId)->first()?->role?->name;
    }

    public static function departmentId($userId)
    {
        return TaskModuleUserRole::where('user_id', $userId)->first()?->department_id;
    }

    public static function department($userId)
    {
        return TaskModuleUserRole::join('departments', 'departments.department_id', '=', 'task_module_user_roles.department_id')
            ->where('task_module_user_roles.user_id', $userId)->first()?->department_name;
    }
}
