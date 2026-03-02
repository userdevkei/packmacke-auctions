<?php

namespace Modules\Tasks\Entities;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class SubTask extends Model
{
    use HasFactory, SoftDeletes;

    // Status constants (same as Task)
    const STATUS_NOT_STARTED = 0;
    const STATUS_IN_PROGRESS = 1;
    const STATUS_COMPLETED = 2;
    const STATUS_CANCELLED = 3;

    protected $primaryKey = 'sub_task_id';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $date = 'deleted_at';

    protected $fillable = ['sub_task_id', 'task_id', 'sub_task_name', 'assigned_to', 'assigned_by', 'due_date', 'sub_task_description', 'creator_id', 'date_completed', 'status'];

    protected static function newFactory()
    {
        return \Modules\Tasks\Database\factories\SubTaskFactory::new();
    }

    public function getDueDateAttribute($value)
    {
        return $value ? Carbon::createFromTimestamp($value) : null;
    }

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

    public function getDateCompletedAttribute($value)
    {
        return $value ? Carbon::createFromTimestamp($value) : null;
    }

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
     * Get the parent task
     */
    public function task()
    {
        return $this->belongsTo(Task::class);
    }

    /**
     * Get the user assigned to this sub-task
     */
//    public function assignedTo()
//    {
//        return $this->belongsTo(User::class, 'assigned_to');
//    }

    // ------------------ Scopes ------------------
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

    // ------------------ Helpers / Attributes ------------------
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
