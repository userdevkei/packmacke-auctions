<?php

namespace Modules\Tasks\Entities;

use App\Models\UserInfo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class TaskAttachment extends Model
{
    use HasFactory, SoftDeletes;

    protected $primaryKey = 'task_attachment_id';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $date = 'deleted_at';
    protected $fillable = ['task_attachment_id', 'task_id', 'file_name', 'file_type', 'uploaded_by'];

    public function creator()
    {
        return $this->belongsTo(UserInfo::class, 'creator_id', 'user_id');
    }
    public function task(){
        return $this->belongsTo(Task::class, 'task_id', 'task_id');
    }

    protected static function newFactory()
    {
        return \Modules\Tasks\Database\factories\TaskAttachmentFactory::new();
    }
}
