<?php

namespace Modules\Tasks\Entities;

use App\Models\Station;
use App\Models\User;
use App\Models\UserInfo;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Modules\Admin\Entities\Department;
use Modules\Tasks\Entities\SubTask;

class Task extends Model
{
    use HasFactory, softDeletes;
    // Status constants
    const STATUS_NOT_STARTED = 0;
    const STATUS_IN_PROGRESS = 1;
    const STATUS_COMPLETED = 2;
    const STATUS_CANCELLED = 3;

    protected $primaryKey = 'task_id';
    protected  $keyType = 'string';
    public $incrementing = false;
    protected $dates = ['deleted_at'];
    protected $fillable = ['task_number', 'task_id', 'task_name', 'department_id', 'description', 'creator_id',  'due_date', 'date_completed', 'priority', 'status', 'station_id', 'assigned_to', 'assigned_by', 'task_date'];

    public function task()
    {
        return $this->belongsTo(Task::class, 'task_id', 'task_id');
    }
    public function creator()
    {
        return $this->belongsTo(UserInfo::class, 'creator_id', 'user_id');
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(UserInfo::class, 'assigned_by', 'user_id' );
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(UserInfo::class, 'assigned_to', 'user_id');
    }
    public function department(){
        return $this->belongsTo(Department::class, 'department_id', 'department_id');
    }
    public function attachments()
    {
        return $this->hasMany(TaskAttachment::class, 'task_id', 'task_id');
    }

    public function location(){
        return $this->belongsTo(Station::class, 'station_id', 'station_id');
    }

    public static function newTaskNumber()
    {
        $year = date('y'); // Get the last two digits of the year
        $prefix = 'T';
        $newID = null;

        DB::beginTransaction();

        try {
            // Get the maximum existing serialized number
            $lastID = self::withTrashed()
                ->where('task_number', 'like', $prefix . '%-' . $year)
                ->orderBy('task_number', 'desc')
                ->lockForUpdate()
                ->first();

            $lastSerialNumber = $lastID ? intval(substr($lastID->task_number, strlen($prefix), strpos($lastID->task_number, '-') - strlen($prefix))) : 0;

            // Increment the serialized number
            $serialNumber = $lastSerialNumber + 1;

            // Generate the full identifier with leading zeros
            $newID = $prefix . str_pad($serialNumber, 4, '0', STR_PAD_LEFT) . '-' . $year;

            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            // Handle or log the exception
        }

        return $newID;
    }
    protected static function newFactory()
    {
        return \Modules\Tasks\Database\factories\TaskFactory::new();
    }

    public function getDueDateAttribute($value)
    {
        return $value ? Carbon::createFromTimestamp($value) : null;
    }

    /**
     * Mutator: accept Carbon/date string and store as unix timestamp
     */
    public function setDueDateAttribute($value)
    {
        if ($value instanceof Carbon) {
            $this->attributes['due_date'] = $value->timestamp;
        } elseif (is_numeric($value)) {
            $this->attributes['due_date'] = (int) $value;
        } else {
            $this->attributes['due_date'] = Carbon::parse($value)->timestamp;
        }
    }

    /**
     * Accessor: return date_completed as Carbon instance (or null)
     */
    public function getDateCompletedAttribute($value)
    {
        return $value ? Carbon::createFromTimestamp($value) : null;
    }

    /**
     * Mutator: accept Carbon/date string and store as unix timestamp
     */
    public function setDateCompletedAttribute($value)
    {
        if ($value instanceof Carbon) {
            $this->attributes['date_completed'] = $value->timestamp;
        } elseif (is_numeric($value)) {
            $this->attributes['date_completed'] = (int) $value;
        } elseif ($value === null) {
            $this->attributes['date_completed'] = null;
        } else {
            $this->attributes['date_completed'] = Carbon::parse($value)->timestamp;
        }
    }

    /**
     * Get the user who is assigned this task
     */
//    public function assignedTo()
//    {
//        return $this->belongsTo(User::class, 'assigned_to');
//    }

    /**
     * Get the user who created this task
     */
//    public function creator()
//    {
//        return $this->belongsTo(User::class, 'created_by');
//    }
//
//    /**
//     * Get the department this task belongs to
//     */
//    public function department()
//    {
//        return $this->belongsTo(Department::class);
//    }
//
//    /**
//     * Get all sub-tasks for this task
//     */
//    public function subTasks()
//    {
//        return $this->hasMany(SubTask::class);
//    }

    /**
     * Get completed sub-tasks
     */
    public function completedSubTasks()
    {
        return $this->hasMany(SubTask::class)->where('status', self::STATUS_COMPLETED);
    }

    /**
     * Get pending sub-tasks
     */
    public function pendingSubTasks()
    {
        return $this->hasMany(SubTask::class)->whereIn('status', [self::STATUS_NOT_STARTED, self::STATUS_IN_PROGRESS]);
    }

    // ----------------------- Scopes -----------------------------
    public function scopeStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeNotStarted($query)
    {
        return $query->where('status', self::STATUS_NOT_STARTED);
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', self::STATUS_IN_PROGRESS);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', self::STATUS_CANCELLED);
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', [self::STATUS_NOT_STARTED, self::STATUS_IN_PROGRESS]);
    }

    public function scopeDueToday($query)
    {
        $start = Carbon::today()->startOfDay()->timestamp;
        $end = Carbon::today()->endOfDay()->timestamp;
        return $query->whereBetween('due_date', [$start, $end]);
    }

    public function scopeOverdue($query)
    {
        $now = Carbon::now()->timestamp;
        return $query->where('due_date', '<', $now)
            ->whereIn('status', [self::STATUS_NOT_STARTED, self::STATUS_IN_PROGRESS]);
    }

    public function scopeAtRisk($query)
    {
        $now = Carbon::now()->timestamp;
        return $query->where('due_date', '<=', $now)
            ->whereIn('status', [self::STATUS_NOT_STARTED, self::STATUS_IN_PROGRESS]);
    }

    public function scopeAssignedTo($query, $userId)
    {
        return $query->where('assigned_to', $userId);
    }

    public function scopeInDepartment($query, $departmentId)
    {
        return $query->where('department_id', $departmentId);
    }

    // ----------------------- Attributes / Helpers -----------------------------
    public function getStatusTextAttribute()
    {
        switch ($this->status) {
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

    public function getStatusColorAttribute()
    {
        switch ($this->status) {
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

    public function getIsOverdueAttribute()
    {
        if (!$this->due_date) return false;
        return $this->due_date->lt(Carbon::today()) &&
            in_array($this->status, [self::STATUS_NOT_STARTED, self::STATUS_IN_PROGRESS]);
    }

    public function getIsAtRiskAttribute()
    {
        if (!$this->due_date) return false;
        return $this->due_date->lte(Carbon::today()) &&
            in_array($this->status, [self::STATUS_NOT_STARTED, self::STATUS_IN_PROGRESS]);
    }

    public function getCompletionPercentageAttribute()
    {
        $totalSubTasks = $this->subTasks()->count();

        if ($totalSubTasks === 0) {
            // If no sub-tasks, return based on main task status
            return $this->status === self::STATUS_COMPLETED ? 100 : 0;
        }

        $completedSubTasks = $this->completedSubTasks()->count();
        return round(($completedSubTasks / $totalSubTasks) * 100, 1);
    }

    public function getTimeSpentAttribute()
    {
        if ($this->date_completed && $this->started_at) {
            return $this->started_at->diffInHours($this->date_completed);
        } elseif ($this->started_at) {
            return $this->started_at->diffInHours(Carbon::now());
        }
        return 0;
    }

    public function getDaysUntilDueAttribute()
    {
        if (!$this->due_date) return null;
        return Carbon::today()->diffInDays($this->due_date, false);
    }

    public function markAsCompleted()
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'date_completed' => Carbon::now(),
        ]);
    }

    public function markAsInProgress()
    {
        $updates = ['status' => self::STATUS_IN_PROGRESS];

        if (!$this->started_at) {
            $updates['started_at'] = Carbon::now();
        }

        $this->update($updates);
    }

    public function markAsCancelled()
    {
        $this->update(['status' => self::STATUS_CANCELLED]);
    }
}
