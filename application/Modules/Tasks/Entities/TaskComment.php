<?php

namespace Modules\Tasks\Entities;

use App\Models\UserInfo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class TaskComment extends Model
{
    use HasFactory, SoftDeletes;

    protected $primaryKey = 'task_comment_id';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $date = 'delete_at';

    protected $fillable = [
        'task_comment_id',
        'task_id',
        'comment',
        'created_by',
    ];

    public function creator()
    {
        return $this->belongsTo(UserInfo::class, 'created_by', 'user_id');
    }

    protected static function newFactory()
    {
        return \Modules\Tasks\Database\factories\TaskCommentFactory::new();
    }
}
