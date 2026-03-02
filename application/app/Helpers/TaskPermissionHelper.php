<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Auth;
use Modules\Tasks\Entities\Task;

class TaskPermissionHelper
{
    public static function can($roleName, $action)
    {
        $permissions = config('task_roles.' . $roleName, []);
        return $permissions[$action] ?? false;
    }

    public static function canEditTask(Task $task)
    {
        $user = Auth::user();

        if (!$user) {
            return false;
        }

        $role = TaskRoleHelper::role($user->user_id) ?? null;
        // Admins and Supervisors can edit all tasks
        if (in_array($role, ['Admin', 'Supervisor'])) {
            return true;
        }

        // Regular users can only edit tasks assigned to them
        return $task->assigned_to == $user->user_id;
    }
}

