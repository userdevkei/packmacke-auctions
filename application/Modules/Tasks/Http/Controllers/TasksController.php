<?php

namespace Modules\Tasks\Http\Controllers;

use App\Events\TaskCommentCreated;
use App\Helpers\TaskPermissionHelper;
use App\Helpers\TaskRoleHelper;
use App\Models\Station;
use App\Models\User;
use App\Models\UserInfo;
use App\Services\CustomIds;
use App\Services\Log;
use Carbon\Carbon;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Modules\Admin\Entities\Department;
use Modules\Tasks\Entities\Notification;
use Modules\Tasks\Entities\NotificationUser;
use Modules\Tasks\Entities\SubTask;
use Modules\Tasks\Entities\SubTaskAttachment;
use Modules\Tasks\Entities\Task;
use Modules\Tasks\Entities\TaskAttachment;
use Modules\Tasks\Entities\TaskComment;
use Modules\Tasks\Entities\TaskModuleRole;
use Modules\Tasks\Entities\TaskModuleUserRole;

class TasksController extends Controller
{
    protected $logger;

    public function __construct(Log $logger){
        $this->logger = $logger;
    }
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    // Status constants

    // Status constants
    const STATUS_NOT_STARTED = 0;
    const STATUS_IN_PROGRESS = 1;
    const STATUS_COMPLETED = 2;
    const STATUS_CANCELLED = 3;


    public function index()
    {
        $user = auth()->user();
        $role = TaskRoleHelper::role($user->user_id);
        $department = TaskRoleHelper::department($user->user_id);

// Get stats based on role
        $stats = $this->getStats($user, $role);
        $tasks = $this->getTasks($user, $role);
        $chartData = $this->getChartData($user, $role);
        $insights = $this->getInsights($user, $role);
        $data = [
            'stats' => $stats,
            'tasks' => $tasks,
            'chartData' => $chartData,
            'insights' => $insights,
            'role' => $role,
            'department' => $department,
        ];


// Add role-specific data
        if ($role === 'Admin' || $role === 'Supervisor') {
            $data['topPerformers'] = $this->getTopPerformers($user, $role);
        } else {
            $data['myOverallPosition'] = $this->getMyPointsAndPosition($user->user_id);
            $data['myPendingTasks'] = Task::where('tasks.assigned_to', $user->user_id)
                ->whereIn('tasks.status', [self::STATUS_NOT_STARTED, self::STATUS_IN_PROGRESS])
                ->orderBy('tasks.due_date', 'asc')
                ->limit(5) // avoid too long list
                ->get(['task_name', 'tasks.due_date', 'priority', 'tasks.status']);
        }

        if ($role === 'Admin') {
            $data['departmentStats'] = $this->getDepartmentStats();
        }

        return view('tasks::welcome', $data);
    }
    /**
     * Get statistics based on user role
     */
    private function getStats($user, $role)
    {
        $query = Task::query();

        /** -----------------------------------------
         * ROLE-BASED FILTERING
         * ------------------------------------------
         * Admin + Supervisor → See ALL tasks
         * All other users   → See only their assigned tasks
         */
        if (!in_array($role, ['Admin', 'Supervisor'])) {
            $query->where('assigned_to', $user->user_id);
        }

        /** -----------------------------------------
         * DATE RANGES (UNIX TIMESTAMPS)
         * ------------------------------------------ */
        $todayStart     = Carbon::today()->startOfDay()->timestamp;
        $todayEnd       = Carbon::today()->endOfDay()->timestamp;
        $yesterdayStart = Carbon::yesterday()->startOfDay()->timestamp;
        $yesterdayEnd   = Carbon::yesterday()->endOfDay()->timestamp;

        /** -----------------------------------------
         * CORE METRICS
         * ------------------------------------------ */

        // Tasks due today
        $todayTasks = (clone $query)->whereBetween('due_date', [$todayStart, $todayEnd])->count();

        // Yesterday (for percentage change)
        $yesterdayTasks = (clone $query)->whereBetween('due_date', [$yesterdayStart, $yesterdayEnd])->count();

        $todayTasksChange = $yesterdayTasks > 0
            ? round((($todayTasks - $yesterdayTasks) / $yesterdayTasks) * 100, 1)
            : 0;

        // Completed today
        $completedToday = (clone $query)
            ->where('status', self::STATUS_COMPLETED)
            ->whereBetween('date_completed', [$todayStart, $todayEnd])
            ->count();

        // Pending today
        $pendingToday = (clone $query)
            ->whereIn('status', [self::STATUS_NOT_STARTED, self::STATUS_IN_PROGRESS, null])
//            ->whereBetween('due_date', [$todayStart, $todayEnd])
            ->count();

        // At risk (overdue or due today, not completed)
        $atRisk = (clone $query)
            ->whereIn('status', [self::STATUS_NOT_STARTED, self::STATUS_IN_PROGRESS])
            ->where('due_date', '<=', $todayEnd)
            ->count();

        // Total active tasks (not completed, not cancelled)
        $totalTasks = (clone $query)
            ->whereIn('status', [self::STATUS_NOT_STARTED, self::STATUS_IN_PROGRESS])
            ->count();

        // All-time counts
        $completedCount = (clone $query)->where('status', self::STATUS_COMPLETED)->count();
        $pendingCount   = (clone $query)->whereIn('status', [
            self::STATUS_NOT_STARTED,
            self::STATUS_IN_PROGRESS
        ])->count();
        $cancelledCount = (clone $query)->where('status', self::STATUS_CANCELLED)->count();


        /** -----------------------------------------
         * PERCENTAGES
         * ------------------------------------------ */
        $totalAllTasks = $completedCount + $pendingCount + $cancelledCount;

        if ($totalAllTasks > 0) {
            $completedPercentage = round(($completedCount / $totalAllTasks) * 100, 1);
            $pendingPercentage   = round(($pendingCount / $totalAllTasks) * 100, 1);
            $atRiskPercentage    = round(($atRisk / $totalAllTasks) * 100, 1);
            $cancelledPercentage = round(($cancelledCount / $totalAllTasks) * 100, 1);
        } else {
            // No tasks at all → all percentages 0
            $completedPercentage = 0;
            $pendingPercentage   = 0;
            $atRiskPercentage    = 0;
            $cancelledPercentage = 0;
        }

        // Daily completion rate
        $completionRate = $todayTasks > 0
            ? round(($completedToday / $todayTasks) * 100, 1)
            : 0;


        /** -----------------------------------------
         * RETURN CLEAN RESULTS
         * ------------------------------------------ */
        return [
            'today_tasks'         => $todayTasks,
            'today_tasks_change'  => $todayTasksChange,
            'completed_today'     => $completedToday,
            'completion_rate'     => $completionRate,

            'pending_today'       => $pendingToday,
            'pending_percentage'  => $totalAllTasks > 0 ? round(($pendingToday / $totalAllTasks) * 100, 1) : 0,

            'at_risk'             => $atRisk,
            'at_risk_percentage'  => $atRiskPercentage,

            'total_tasks'         => $totalTasks,

            'completed_count'     => $completedCount,
            'completed_percentage'=> $completedPercentage,

            'pending_count'       => $pendingCount,
            'pending_percentage'  => $pendingPercentage,

            'cancelled_count'     => $cancelledCount,
            'cancelled_percentage'=> $cancelledPercentage,
        ];
    }

    /**
     * Get tasks list based on user role
     */
    private function getTasks($user, $role, $limit = 20)
    {
        $query = Task::with(['assignedTo', 'department']);

// Filter based on role
        if (!in_array($role, ['Admin', 'Supervisor'])) {
            $query->where('assigned_to', $user->user_id);
        }

        $priorities = [
            '1' => 'Critical',
            '2' => 'Very Urgent',
            '3' => 'Medium Urgency',
            '4' => 'Low Urgency',
        ];

        $pColors = [
            '1' => 'danger',
            '2' => 'warning',
            '3' => 'info',
            '4' => 'primary',
        ];

        $startOfDay = now()->startOfDay()->timestamp;
        $endOfDay = now()->endOfDay()->timestamp;
// Get recent active tasks
        $tasks = $query->whereBetween('tasks.task_date', [$startOfDay, $endOfDay])
            ->orWhere(function ($sQuery) {
                $sQuery->whereIn('tasks.status', [
                        self::STATUS_NOT_STARTED,
                        self::STATUS_IN_PROGRESS
                    ]);
            })

        ->orderBy('priority', 'asc') // <-- sort by priority first
            ->orderBy('tasks.created_at', 'desc') // then by due date
            ->limit($limit)
            ->get()
            ->map(function ($task) use ($priorities, $pColors) {
                $priorityKey = (string)($task->priority ?? '4'); // Default to '4' (Low Urgency) if null
                $priorityLabel = $priorities[$priorityKey] ?? 'Normal';
                $pColor = $pColors[$priorityKey] ?? 'info';

                return [
                    'id' => $task->task_number,
                    'title' => $task->task_name,
                    'status' => $this->getStatusText($task->status),
                    'status_color' => $this->getStatusColor($task->status),
                    'department' => $task->department->department_name,
                    'due_date' => $task->due_date ? $task->due_date->toDateTimeString() : null,
                    'priority' => $priorityLabel,
                    'priority_color' => $pColor,
                    'assignedTo' => $task->assignedTo?->first_name.' '.$task->assignedTo?->surname,
                    'task_date' => $task->task_date ? Carbon::createFromTimestamp($task->task_date)->format('d/m/y') : null,
                ];
            });
        return $tasks;
    }
    /**
     * Get top performers based on completed tasks and completion time
     */
    private function getMyPointsAndPosition($userId)
    {
        $startDate = Carbon::now()->subDays(30);

        // Get all users with their points + completed task count
        $ranked = User::leftJoin('tasks', 'users.user_id', '=', 'tasks.assigned_to')
            ->where('tasks.status', self::STATUS_COMPLETED)
            // ->where('tasks.date_completed', '>=', $startDate->timestamp)
            ->select('users.user_id')
            ->selectRaw('
            COUNT(tasks.task_id) as completed_tasks,
            SUM(CASE WHEN tasks.date_completed <= tasks.due_date THEN 5 ELSE 0 END) as points
        ')
            ->groupBy('users.user_id')
            ->orderByDesc('points')
            ->get();

        // Re-index to get correct ranks
        $ranked = $ranked->values();

        // Initialize defaults
        $position = null;
        $points = 0;
        $completedTasks = 0;

        // Find current user's data
        foreach ($ranked as $index => $row) {
            if ($row->user_id == $userId) {
                $position = $index + 1; // Rank starts at 1
                $points = $row->points ?? 0;
                $completedTasks = $row->completed_tasks ?? 0;
                break;
            }
        }

        return [
            'position' => $position,
            'points' => $points,
            'completed_tasks' => $completedTasks
        ];
    }

    private function getTopPerformers($user, $role, $limit = 6)
    {
        $query = User::query();
        $departmentId = TaskRoleHelper::departmentId($user->user_id);

        // Filter for supervisors
        if ($role === 'Supervisor') {
            $query->join('task_module_user_roles', 'task_module_user_roles.user_id', '=', 'users.user_id')
                ->where('tasks.department_id', $departmentId);
        }

        $startDate = Carbon::now()->subDays(30);

        $departments = [
            '1' => 'Admin',
            '2' => 'Stocks & Tea Inventory',
            '3' => 'Stocks & Tea Inventory',
            '5' => 'Shipping & Logistics',
            '6' => 'Shipping & Logistics',
            '7' => 'Finance & Accounting',
            '8' => 'Finance & Accounting',
            '9' => 'Finance & Accounting',
        ];

        $performers = $query->join('user_infos', 'user_infos.user_id', '=', 'users.user_id')
            ->leftJoin('tasks', 'users.user_id', '=', 'tasks.assigned_to')
            ->where('tasks.status', self::STATUS_COMPLETED)
            ->where('tasks.date_completed', '>=', $startDate->timestamp)
            ->select('users.*', 'surname', 'first_name')
            ->selectRaw('
            COUNT(tasks.task_id) as completed_tasks,
            SUM(CASE WHEN tasks.date_completed <= tasks.due_date THEN 1 ELSE 0 END) as on_time_tasks
        ')
            ->groupBy('users.user_id')
            ->having('completed_tasks', '>', 0)
            ->get()
            ->map(function ($performer) use ($departments) {
                // Score: 5 points per on-time task
                $score = ($performer->on_time_tasks ?? 0) * 5;

                // Determine department
                $department = 'N/A';
                if ($performer->role_id == 1) {
                    $department = 'Admin';
                } elseif (isset($departments[$performer->role_id])) {
                    $department = $departments[$performer->role_id];
                } elseif (!empty($performer->department->department_name)) {
                    $department = $performer->department->department_name;
                }

                return [
                    'name' => $performer->first_name . ' ' . $performer->surname,
                    'role' => $performer->role_display ?? ucfirst($performer->role ?? 'User'),
                    'department' => $department,
                    'avatar_url' => $performer->avatar_url ?? asset('assets/img/team/avatar.png'),
                    'completed_tasks' => $performer->completed_tasks,
                    'on_time_tasks' => $performer->on_time_tasks,
                    'score' => $score,
                ];
            })
            // Sort by score (highest → lowest)
            ->sortByDesc('score')
            ->take($limit)
            ->values();

        return $performers;
    }
    /**
     * Get department statistics (Admin only)
     */
    private function getDepartmentStats()
    {
        $startDate = Carbon::now()->subDays(30);
        $departments = Department::select('departments.*')
            ->selectRaw('COUNT(CASE WHEN tasks.status = ? THEN 1 END) as completed_tasks', [self::STATUS_COMPLETED])
            ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, tasks.created_at, FROM_UNIXTIME(tasks.date_completed))) as avg_completion_hours')
            ->selectRaw('COUNT(tasks.task_id) as total_tasks')
            ->leftJoin('tasks', 'departments.department_id', '=', 'tasks.department_id')
//            ->where('tasks.created_at', '>=', $startDate)
            ->groupBy('departments.department_id')
            ->orderByDesc('completed_tasks')
            ->get()
            ->map(function ($dept) {
                $performancePercentage = $dept->total_tasks > 0
                    ? round(($dept->completed_tasks / $dept->total_tasks) * 100, 1)
                    : 0;


                return [
                    'name' => $dept->department_name,
                    'completed_tasks' => $dept->completed_tasks,
                    'avg_completion_hours' => $dept->avg_completion_hours ?? 0,
                    'performance_percentage' => $performancePercentage,
                    'performance_color' => $performancePercentage >= 80 ? 'success'
                        : ($performancePercentage >= 60 ? 'info'
                            : ($performancePercentage >= 40 ? 'warning' : 'danger')),
                ];
            });


        return $departments;
    }
    /**
     * Get chart data for task distribution over time
     */
    private function getChartData($user, $role, $days = 30)
    {
        $query = Task::query();
        if (!in_array($role, ['Admin', 'Supervisor'])) {
            $query->where('assigned_to', $user->user_id)->whereNotNull('assigned_to');
        }

        $labels = [];
        $completed = [];
        $pending = [];
        $atRisk = [];


        for ($i = $days; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $dateStart = $date->copy()->startOfDay()->timestamp;
            $dateEnd = $date->copy()->endOfDay()->timestamp;


            $labels[] = $date->format('M d');


// Completed on this day (date_completed stored as unix timestamp)
            $completed[] = (clone $query)
                ->where('status', self::STATUS_COMPLETED)
                ->whereBetween('date_completed', [$dateStart, $dateEnd])
                ->count();


// Pending on this day (created before or on this day, not completed)
            $pending[] = (clone $query)
                ->whereIn('status', [self::STATUS_NOT_STARTED, self::STATUS_IN_PROGRESS])
                ->where('created_at', '<=', Carbon::createFromTimestamp($dateEnd))
                ->count();


// At risk on this day
            $atRisk[] = (clone $query)
                ->whereIn('status', [self::STATUS_NOT_STARTED, self::STATUS_IN_PROGRESS])
                ->where('due_date', '<=', $dateEnd)
                ->where('created_at', '<=', Carbon::createFromTimestamp($dateEnd))
                ->count();
        }


        return [
            'labels' => $labels,
            'completed' => $completed,
            'pending' => $pending,
            'at_risk' => $atRisk,
        ];
    }
    /**
     * Get insights and analytics
     */
    private function getInsights($user, $role)
    {
        $query = Task::query();

        // Filter based on role
        if ($role === 'supervisor') {
            $query->where('department_id', $user->department_id);
        } elseif ($role === '') {
            $query->where('assigned_to', $user->user_id);
        }

        $currentPeriodStart = Carbon::now()->subDays(7)->startOfDay()->timestamp;
        $previousPeriodStart = Carbon::now()->subDays(14)->startOfDay()->timestamp;
        $previousPeriodEnd = Carbon::now()->subDays(7)->endOfDay()->timestamp;

        // --- Completed Tasks (current & previous period) ---
        $currentCompleted = (clone $query)
            ->where('status', self::STATUS_COMPLETED)
            ->where('date_completed', '>=', $currentPeriodStart)
            ->count();

        $previousCompleted = (clone $query)
            ->where('status', self::STATUS_COMPLETED)
            ->whereBetween('date_completed', [$previousPeriodStart, $previousPeriodEnd])
            ->count();

        // --- Productivity Trend ---
        $productivityTrend = $previousCompleted > 0
            ? round((($currentCompleted - $previousCompleted) / $previousCompleted) * 100, 1)
            : 0;
        $productivityDirection = $productivityTrend >= 0 ? 'increase' : 'decrease';

        // --- Average Completion Time (in hours) ---
        $avgCompletionTime = (clone $query)
            ->where('status', self::STATUS_COMPLETED)
            ->where('date_completed', '>=', $currentPeriodStart)
            ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, created_at, FROM_UNIXTIME(date_completed))) as avg_time')
            ->value('avg_time');

        $avgCompletionTime = round($avgCompletionTime ?? 0, 1);

        // --- Ranking & Team Performance ---
        $userRank = 1;
        $totalUsers = 1;
        $teamPerformance = 75;

        if ($role === 'user') {
            // Calculate user ranking based on completed tasks
            $rankings = User::select('users.user_id')
                ->selectRaw('COUNT(CASE WHEN tasks.status = ? THEN 1 END) as completed', [self::STATUS_COMPLETED])
//                ->leftJoin('sub_tasks as tasks', 'users.user_id', '=', 'tasks.assigned_to')
                ->where('tasks.date_completed', '>=', $currentPeriodStart)
                ->groupBy('users.user_id')
                ->orderByDesc('completed')
                ->pluck('id');

            $userRank = $rankings->search($user->user_id) !== false ? $rankings->search($user->user_id) + 1 : $rankings->count() + 1;
            $totalUsers = $rankings->count();
        } else {
            // Dynamically compute team performance for admin/supervisor
            $teamQuery = User::query();
            if ($role === 'supervisor') {
                $teamQuery->where('department_id', $user->department_id);
            }

            $teamUserIds = $teamQuery->pluck('user_id');

            // Count completed tasks in current period
            $teamStats = Task::whereIn('assigned_to', $teamUserIds)
                ->where('tasks.status', self::STATUS_COMPLETED)
                ->where('tasks.date_completed', '>=', $currentPeriodStart)
                ->selectRaw('COUNT(*) as total_completed, COUNT(DISTINCT assigned_to) as active_users')
                ->first();

            $totalCompleted = $teamStats->total_completed ?? 0;
            $activeUsers = max($teamStats->active_users ?? 1, 1);

            // Average completed tasks per user, scaled 0–100
            $teamPerformance = round(($totalCompleted / $activeUsers) * 10, 1);
            if ($teamPerformance > 100) {
                $teamPerformance = 100;
            }
        }

        return [
            'productivity_trend' => abs($productivityTrend),
            'productivity_direction' => $productivityDirection,
            'avg_completion_time' => $avgCompletionTime,
            'user_rank' => $userRank,
            'total_users' => $totalUsers,
            'team_performance' => $teamPerformance,
        ];
    }
    /**
     * Helper function to get status text
     */
    private function getStatusText($status)
    {
        switch ($status) {
            case self::STATUS_NOT_STARTED:
                return 'Not Started';
            case self::STATUS_IN_PROGRESS:
                return 'In Progress';
            case self::STATUS_COMPLETED:
                return 'Completed';
            case self::STATUS_CANCELLED:
                return 'Cancelled';
            default:
                return 'Unknown';
        }
    }
    /**
     * Helper function to get status color
     */
    private function getStatusColor($status)
    {
        switch ($status) {
            case self::STATUS_NOT_STARTED:
                return 'secondary';
            case self::STATUS_IN_PROGRESS:
                return 'info';
            case self::STATUS_COMPLETED:
                return 'success';
            case self::STATUS_CANCELLED:
                return 'danger';
            default:
                return 'secondary';
        }
    }
    /**
     * Helper function to get priority color
     */
    private function getPriorityColor($priority)
    {
        switch (strtolower($priority)) {
            case 'high':
            case 'urgent':
                return 'danger';
            case 'medium':
            case 'normal':
                return 'warning';
            case 'low':
                return 'info';
            default:
                return 'secondary';
        }
    }
    /**
     * AJAX endpoint to filter tasks
     */
    public function filterTasks(Request $request)
    {
        $user = auth()->user();
        $role = $user->role;
        $filter = $request->input('filter', 'all');

        $query = Task::with(['assignedTo', 'department']);

// Apply role-based filtering
        if ($role === 'supervisor') {
            $query->where('department_id', $user->department_id);
        } elseif ($role === '') {
            $query->where('assigned_to', $user->user_id);
        }


// Apply status filter
        switch ($filter) {
            case 'pending':
                $query->whereIn('status', [self::STATUS_NOT_STARTED, self::STATUS_IN_PROGRESS]);
                break;
            case 'completed':
                $query->where('status', self::STATUS_COMPLETED);
                break;
            case 'at_risk':
                $query->whereIn('status', [self::STATUS_NOT_STARTED, self::STATUS_IN_PROGRESS])
                    ->where('due_date', '<=', Carbon::today()->endOfDay()->timestamp);
                break;
            case 'cancelled':
                $query->where('status', self::STATUS_CANCELLED);
                break;
        }


        $tasks = $query->orderBy('due_date', 'asc')
            ->limit(20)
            ->get()
            ->map(function ($task) {
                return [
                    'id' => $task->id,
                    'title' => $task->title,
                    'status' => $this->getStatusText($task->status),
                    'status_color' => $this->getStatusColor($task->status),
                    'assigned_to_name' => $task->assignedTo->name ?? 'Unassigned',
                    'due_date' => $task->due_date ? $task->due_date->format('M d, Y') : null,
                    'priority' => $task->priority ?? 'Normal',
                    'priority_color' => $this->getPriorityColor($task->priority ?? 'Normal'),
                ];
            });


        return response()->json($tasks);
    }

    /*----------------------------------------------------------------------------------------------------------------- END OF DASHBOARD ---------------------------------------------------------------------*/
    public function tasks()
    {
       $user = auth()->user();
        $role = TaskRoleHelper::role($user->user_id);
        $departmentId = TaskRoleHelper::departmentId($user->user_id);

        $query = Task::query();

        switch ($role) {
            case 'Admin':
                // Admins see all tasks — no filters
                break;

            case 'Supervisor':
                break;
            case null:
            default:
                // Regular users: see only tasks assigned to them
                $query->where('assigned_to', $user->user_id);
                break;
        }

        $tasks = $query->with([
            'creator:user_id,first_name,surname',
            'department:department_id,department_name',
            'assignedTo:user_id,first_name,surname',
            'assignedBy:user_id,first_name,surname',
        ])
            ->withCount([
                'attachments',
            ])
            ->latest()
            ->get();

        $departments = Department::all();
        $locations = Station::all();
        $users = User::join('user_infos', 'user_infos.user_id', '=', 'users.user_id')->where('status', 1)->get();
        return view('tasks::task.index', compact('tasks', 'role', 'departments', 'locations', 'users'));
    }
    public function addTask()
    {
        $users = UserInfo::join('users', 'users.user_id', '=', 'user_infos.user_id')->where('status', 1)->get();
        $locations = Station::all();
        $departments = Department::all();
        return view('tasks::task.addTask', compact('users', 'locations', 'departments'));
    }
    public function registerTask (Request $request)
    {
        $tasks = $request->tasks;
        DB::beginTransaction();
        try {
            foreach ($tasks as $task) {
                // Create the task
                $job = Task::create([
                    'task_id' => (new CustomIds())->generateId(),
                    'task_number' => Task::newTaskNumber(),
                    'task_name' => $task['name'],
                    'department_id' => $task['department'],
                    'station_id' => $request->location,
                    'assigned_to' => $task['assigned_to'] ?? null,
                    'assigned_by' => !empty($task['assigned_to']) ? auth()->user()->user_id : null,
                    'task_date' => strtotime($request->general_date),
                    'due_date' => !empty($task['deadline']) ? strtotime($task['deadline']) : null,
                    'description' => $task['description'] ?? null,
                    'creator_id' => auth()->user()->user_id,
                    'priority' => $task['priority'] ?? 1,
                ]);

                $action = [
                    'type' => 'task_created',
                    'title' => 'New task created',
                    'message' => 'New Task '.$task['name'].' has been created.',
                ];
                $this->createTaskNotification($job, $action);

                // ✅ Handle attachments (if provided)
                if (!empty($task['attachments']) && is_array($task['attachments'])) {
                    foreach ($task['attachments'] as $attachment) {
                        // if you're receiving uploaded files directly from the request,
                        // use $attachment as a file object
                        if (isset($attachment) && $attachment instanceof \Illuminate\Http\UploadedFile) {
                            // Generate unique filename
                            $extension = $attachment->getClientOriginalExtension();
                            $uniqueName = uniqid('task_') . '.' . $extension;

                            // Store in disk (e.g., 'attachments' => storage/app/attachments)
                            $path = Storage::disk('attachments')->putFileAs('task_attachments', $attachment, $uniqueName);

                            // Save record
                            TaskAttachment::create([
                                'task_attachment_id' => (new CustomIds())->generateId(),
                                'task_id' => $job['task_id'],
                                'file_name' => $uniqueName,
                                'file_type' => $extension,
                                'uploaded_by' => auth()->user()->user_id,
                            ]);
                        }
                    }
                }
            }
            DB::commit();
            $this->logger->create();
            return redirect()->route('tasks.all')->with('success', 'Task created successfully.');
        }catch (\Exception $exception){
            DB::rollBack();
            return redirect()->back()->with('error', $exception->getMessage());
        }
    }

    private function createTaskNotification($task, $action)
    {
        // 1. CREATE notification
        $notification = Notification::create([
            'id' => (new CustomIds())->generateId(),
            'type' => $action['type'],
            'title' => $action['title'],
            'message' => $action['message'],
            'data' => ['task_id' => $task->task_number, 'description' => $task->description, 'deadline' => $task->due_date],
            'created_by' => auth()->id(),
        ]);

        // 2. Fetch recipients
        $admins = DB::table('task_module_user_roles')->pluck('user_id')->toArray();
        $assignedUser = $task->assigned_to ? [$task->assigned_to] : [];

        $recipients = array_unique(array_merge($admins, $assignedUser));

        // REMOVE NULL VALUES
        $recipients = array_filter($recipients);
        // 3. Insert into notification_users
        foreach ($recipients as $userId) {
            NotificationUser::create([
                'id' => (new CustomIds())->generateId(),
                'notification_id' => $notification->id,
                'user_id' => $userId
            ]);
        }
    }

    public function updateTask (Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $task = Task::findOrFail($id);
            $user = auth()->user();
            $role = TaskRoleHelper::role($user->user_id);
            $updateData = [];

            if (TaskPermissionHelper::can($role, 'modify-task')) {
                $updateData = [
                    'task_name' => $request->task,
                    'department_id' => $request->department,
                    'due_date' => strtotime($request->date_due),
                    'description' => $request->description ?? null,
                    'priority' => $request->priority,
                    'status' => $request->status,
                    'assigned_to' => $request->assign_to ?? null,
                    'date_completed' => $request->status == 2 ? time() : null,
                    'station_id' => $request->location,
                ];

                $currentUserId = auth()->user()->user_id;

                if (
                    ($task->assigned_to === null && $request->assign_to !== null) ||
                    ($task->assigned_by !==  $currentUserId && $request->assign_to !== $task->assigned_to)
                ) {
                    $updateData['assigned_by'] = $currentUserId;
                }

            }else{
                $updateData = [
                    'status' => $request->status,
                    'date_completed' => $request->status == 2 ? time() : null,
                ];
            }

            Task::where('task_id', $id)->update($updateData);

            $job = Task::where('task_id', $id)->first();
            $action = [
                'type' => 'task_updated',
                'title' => 'Task Updated',
                'message' => 'Task '.$job->task_name.' has been updated.',
            ];
            $this->createTaskNotification($job, $action);

            // ✅ Handle attachments
            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    // Generate unique filename
                    $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                    $extension = $file->getClientOriginalExtension();
                    $uniqueName = uniqid() . '.' . $extension;

                    // Store using your custom disk 'attachments'
                    $path = Storage::disk('attachments')->putFileAs('task_attachments/', $file, $uniqueName);

                    // Save attachment record
                    TaskAttachment::create([
                        'task_attachment_id' => (new CustomIds())->generateId(),
                        'task_id' => $id,
                        'file_name' => $uniqueName,
                        'file_type' => $extension,
                        'uploaded_by' => auth()->user()->user_id,
                    ]);
                }
            }
            DB::commit();
            $this->logger->create();
            return redirect()->back()->with('success', 'Task updated successfully.');
        }catch (\Exception $exception){
            DB::rollBack();
            return redirect()->back()->with('error', $exception->getMessage());
        }
    }
    public function deleteTask($id){
        DB::beginTransaction();
        try {

            $job =  Task::find($id)->first();
            $action = [
                'type' => 'task_deleted',
                'title' => 'Task Deleted',
                'message' => 'Task '.$job->task_name.' has been deleted.',
            ];
            $this->createTaskNotification($job, $action);

            SubTask::where('task_id', $id)->delete();
            Task::find($id)->delete();
            DB::commit();
            $this->logger->create();
            return redirect()->route('tasks.all')->with('success', 'Task deleted successfully.');
        }catch (\Exception $exception){
            DB::rollBack();
            return redirect()->back()->with('error', $exception->getMessage());
        }
    }
    public function viewTask($id)
    {
       $task = Task::with(['creator', 'department', 'attachments', 'location', 'assignedTo'])->findOrFail($id);

        $files = TaskAttachment::join('user_infos', 'user_infos.user_id', '=', 'task_attachments.uploaded_by')
            ->select('task_attachment_id as file_id', 'surname', 'file_name', 'file_type', DB::raw("'Task File' as type"))
            ->where('task_id', $id)->get();


        $percentage = $task->status == 1 ? 50 : ($task->status == 2 ? 100 : 0);

// Priority color mapping
        $priorityColors = [
            '1' => 'danger',
            '2' => 'warning',
            '3' => 'info',
            '4' => 'success',
        ];
        $priorityColor = $priorityColors[strtolower($task->priority)] ?? 'secondary';

// Status color mapping
        $statusColors = [
            '0' => 'secondary',
            '1' => 'info',
            '2' => 'success',
            '3' => 'danger',
        ];
        $statusColor = $statusColors[strtolower($task->status)] ?? 'secondary';

        $statuses = [
            '0' => 'pending',
            '1' => 'in progress',
            '2' => 'Completed',
            '3' => 'Canceled',
        ];
        $status = $statuses[strtolower($task->status)] ?? 'pending';

        $priorities = [
            '1' => 'Critical',
            '2' => 'Very Urgent',
            '3' => 'Medium Urgency',
            '4' => 'Low Urgency',
        ];
        $priority = $priorities[ucwords(strtolower($task->priority))] ?? 'Low Urgency';

// Risk calculation (based on due date and unfinished subtasks)
        $dueHours = now()->diffInHours($task->due_date, false);
        $remaining = $task->status == 2 ? 0 : 1;
        $completed = $task->status == 2 ? 1 : 0;
        $total_subtasks = 1;

        if ($task->status == 2){
            $riskLevel = 'Competed';
            $riskColor = 'success';
            $riskDescription = 'No risk, Competed!';
        }else {
            if ($dueHours < 0) {
                $riskLevel = 'Critical';
                $riskColor = 'danger';
                $riskDescription = 'Past due!';
            } elseif ($remaining > 0 && $dueHours < 12) {
                $riskLevel = 'High';
                $riskColor = 'warning';
                $riskDescription = 'Tight deadline with pending subtasks.';
            } elseif ($remaining > 0 && $dueHours < 48) {
                $riskLevel = 'Moderate';
                $riskColor = 'info';
                $riskDescription = 'Manageable timeline.';
            } else {
                $riskLevel = 'Low';
                $riskColor = 'success';
                $riskDescription = 'On track.';
            }
        }

        $comments = TaskComment::with('creator')
            ->where('task_id', $id)
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function ($c) {
                return [
                    'id' => $c->task_comment_id,
                    'text' => $c->comment,
                    'timestamp' => Carbon::parse($c->created_at)->format('d/m/y H:i'),
                    'user' => ucwords(strtolower($c->creator->surname)) ?? 'Unknown',
                    'is_own' => $c->created_by === auth()->id(), // 👈 adds a simple boolean
                    'user_id' => $c->created_by,
                ];
            });

        $users = UserInfo::join('users', 'users.user_id', '=', 'user_infos.user_id')->where('status', 1)->get();
        $locations = Station::all();
        $departments = Department::all();
        $userStats = $this->getMyPointsAndPosition($task->assigned_to);
        return view('tasks::task.subtasks', compact('id', 'task', 'files', 'riskLevel', 'riskColor', 'riskDescription', 'statusColor', 'priorityColor', 'percentage', 'completed', 'remaining', 'total_subtasks', 'priority', 'status', 'comments', 'users', 'locations', 'departments', 'userStats'));
    }
    public function viewFile($id){
        $file = TaskAttachment::find($id);
        if (!$file) {
            $file = SubTaskAttachment::find($id);
        }
        return response()->file(
            'Files/uploads/attachments/task_attachments/' . $file->file_name,
            ['Content-Disposition' => 'inline']
        );

    }
    public function deleteFile($id)
    {
        $file = TaskAttachment::find($id);
        if (!$file) {
            $file = SubTaskAttachment::find($id);
        }
        $file->delete();
        $this->logger->create();
        return redirect()->back()->with('success', 'File deleted successfully.');
    }

    public function addSubtask (Request $request, $id)
    {
        DB::beginTransaction();
        try {
            // Create the task
            $task = SubTask::create([
                'sub_task_id' => (new CustomIds())->generateId(),
                'task_id' => $id,
                'sub_task_name' => $request->task,
                'assigned_to' => $request->staff,
                'assigned_by' => $request->staff == null ? null : auth()->user()->user_id,
                'due_date' => strtotime($request->date_due),
                'sub_task_description' => $request->description,
                'creator_id' => auth()->user()->user_id,
            ]);

            // ✅ Handle attachments
            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    // Generate unique filename
                    $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                    $extension = $file->getClientOriginalExtension();
                    $uniqueName = uniqid() . '.' . $extension;

                    // Store using your custom disk 'attachments'
                    $path = Storage::disk('attachments')->putFileAs('task_attachments/', $file, $uniqueName);

                    // Save attachment record
                    SubTaskAttachment::create([
                        'sub_task_attachment_id' => (new CustomIds())->generateId(),
                        'sub_task_id' => $task['sub_task_id'],
                        'file_name' => $uniqueName,
                        'file_type' => $extension,
                        'uploaded_by' => auth()->user()->user_id,
                    ]);
                }
            }

            DB::commit();
            return redirect()->route('tasks.viewTask', $id)->with('success', 'Task updated successfully.');
        }catch (\Exception $exception){
            DB::rollBack();
            return redirect()->back()->with('error', $exception->getMessage());
        }
    }
    public function updateSubtask (Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $stask = SubTask::findOrFail($id);

            $updateData = [
                'sub_task_name'        => $request->task,
                'assigned_to'          => $request->staff,
                'due_date'             => strtotime($request->date_due),
                'sub_task_description' => $request->description,
                'status'               => $request->status,
                'date_completed'       => $request->status == 2 ? time() : null,
            ];

            $currentUserId = auth()->user()->user_id;

            if (
                ($stask->assigned_to === null && $request->staff !== null) ||
                ($stask->assigned_by !==  $currentUserId && $request->staff !== $stask->assigned_to)
            ) {
                $updateData['assigned_by'] = $currentUserId;
            }

            SubTask::where('sub_task_id', $id)->update($updateData);

            // ✅ Handle attachments
            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    // Generate unique filename
                    $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                    $extension = $file->getClientOriginalExtension();
                    $uniqueName = uniqid() . '.' . $extension;

                    // Store using your custom disk 'attachments'
                    Storage::disk('attachments')->putFileAs('task_attachments/', $file, $uniqueName);

                    // Save attachment record
                    SubTaskAttachment::create([
                        'sub_task_attachment_id' => (new CustomIds())->generateId(),
                        'sub_task_id' => $id,
                        'file_name' => $uniqueName,
                        'file_type' => $extension,
                        'uploaded_by' => auth()->user()->user_id,
                    ]);
                }
            }
            $complete = SubTask::where(['task_id' => $stask->task_id])->where('status', '<', 2)->get();
            if ($complete->count() === 0) {
                Task::where(['task_id' => $stask->task_id])->update(['status' => 2, 'date_completed' => time()]);
            }elseif ($complete->count() > 0) {
                Task::where(['task_id' => $stask->task_id])->update(['status' => 1, 'date_completed' => time()]);
            }
            DB::commit();
            return redirect()->back()->with('success', 'Task updated successfully.');
        }catch (\Exception $exception){
            DB::rollBack();
            return redirect()->back()->with('error', $exception->getMessage());
        }
    }
    public function deleteSubtask($id){
        SubTask::find($id)->delete();
        return redirect()->back()->with('success', 'Subtask deleted successfully.');
    }
    public function manageUsers()
    {
        $staff = User::join('user_infos', 'users.user_id', '=', 'user_infos.user_id')
            ->leftJoin('task_module_user_roles', 'task_module_user_roles.user_id', '=', 'users.user_id')
            ->leftJoin('task_module_roles', 'task_module_roles.id', '=', 'task_module_user_roles.role_id')
            ->leftJoin('departments', 'departments.department_id', '=', 'task_module_user_roles.department_id')
            ->leftJoin('user_infos as ui', 'ui.user_id', '=', 'task_module_user_roles.assigned_by')
            ->where('users.status', 1)
            ;

        $users = (clone $staff)->whereNotNull('task_module_user_roles.role_id')
            ->select('users.user_id', 'username', DB::raw("CONCAT(COALESCE(user_infos.first_name, ''),' ',COALESCE(user_infos.surname, '')) as staff_name"), DB::raw("CONCAT(COALESCE(ui.first_name, ''),' ',COALESCE(ui.surname, '')) as creator"), 'departments.department_name', 'task_module_roles.name', 'user_role_id', 'departments.department_id')
            ->whereNull('task_module_user_roles.deleted_at')
            ->get();
        $newUsers = (clone $staff)->whereNull('task_module_user_roles.role_id')
            ->select(DB::raw("CONCAT(COALESCE(user_infos.first_name, ''),' ',COALESCE(user_infos.surname, '')) as staff_name"), 'users.user_id', 'users.username', 'departments.department_id')
            ->orWhereNotNull('task_module_user_roles.deleted_at')
            ->get();
        $departments = Department::all();
        $roles = TaskModuleRole::all();

        return view('tasks::users.users', compact('users', 'departments', 'roles', 'newUsers' ));
    }
    public function updateUserRole(Request $request)
    {
        TaskModuleUserRole::updateOrCreate(
            ['user_id' => $request->user_id],
            [
                'user_role_id' => (new CustomIds())->generateId(),
                'role_id' => $request->role_id,
                'department_id' => $request->department_id,
                'assigned_by' => auth()->user()->user_id,
            ]
        );
        $this->logger->create();
        return redirect()->back()->with('success', 'User role updated successfully.');
    }
    public function deleteUserRole($id){
        TaskModuleUserRole::find($id)->delete();
        $this->logger->create();
        return redirect()->back()->with('success', 'User role deleted successfully.');
    }
    public function storeComment(Request $request)
    {
        $request->validate([
            'task_id' => 'required|string|exists:tasks,task_id',
            'message' => 'required|string|max:1000',
        ]);

        $comment = TaskComment::create([
            'task_comment_id' => (new CustomIds())->generateId(),
            'task_id'       => $request->task_id,
            'comment'       => $request->message,
            'created_by'    => Auth::user()->user_id,
        ]);
        $this->logger->create();
        return response()->json([
            'success' => true,
            'message' => 'Comment saved successfully.',
            'data' => $comment
        ]);
    }

    public function getComments($taskId)
    {
        try {
            $comments = TaskComment::with('creator')
                ->where('task_id', $taskId)
                ->orderBy('created_at', 'asc')
                ->get()
                ->map(function ($c) {
                    return [
                        'id' => $c->task_comment_id,
                        'text' => $c->comment,
                        'timestamp' => Carbon::parse($c->created_at)->format('d/m/y H:i'),
                        'user' => ucwords(strtolower($c->creator->surname)) ?? 'Unknown',
                        'is_own' => $c->created_by === auth()->id(), // 👈 adds a simple boolean
                        'user_id' => $c->created_by,
                    ];
                });

            return response()->json([
                'success' => true,
                'comments' => $comments
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch comments'
            ], 500);
        }
    }

    public function list()
    {
        $items = NotificationUser::with('notification', 'notification.creator')
            ->where('user_id', auth()->id())
            ->orderBy('created_at', 'DESC')
            ->get();

        return response()->json($items);
    }

    public function details($id)
    {
        $record = NotificationUser::with([
            'notification.creator',
            'notification.users.user'
        ])
            ->where('id', $id)
            ->where('user_id', auth()->id())
            ->first();

        // Mark as read for ONLY the current user
        if ($record && $record->is_read == 0) {
            $record->update([
                'is_read' => 1,
                'read_at' => now(),
            ]);
        }

        $isAdmin = DB::table('task_module_user_roles')->where(['user_id' => Auth::id(), 'role_id' => 1])->first();

        return response()->json([
            'details' => $record,
            'readers' => $isAdmin ? [
                'read_by' => $record->notification->users->where('is_read', 1)->pluck('user'),
                'pending' => $record->notification->users->where('is_read', 0)->pluck('user'),
            ] : null
        ]);
    }
}
