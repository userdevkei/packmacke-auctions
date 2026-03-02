<?php

namespace Modules\Tasks\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class SubTaskAttachment extends Model
{
    use HasFactory, SoftDeletes;

    protected $primaryKey = 'sub_task_attachment_id';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $date = 'deleted_at';
    protected $fillable = ['sub_task_attachment_id', 'sub_task_id', 'file_name', 'file_type', 'uploaded_by'];

    protected static function newFactory()
    {
        return \Modules\Tasks\Database\factories\SubTaskAttachmentFactory::new();
    }
}
