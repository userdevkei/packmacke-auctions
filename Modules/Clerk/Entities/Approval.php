<?php

namespace Modules\Clerk\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Approval extends Model
{
    use HasFactory, SoftDeletes;

    protected $primaryKey = 'approval_id';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $date = 'deleted_at';
    protected $fillable = ['approval_id', 'job_id', 'user_id', 'approval_date', 'order'];

    protected static function newFactory()
    {
        return \Modules\Clerk\Database\factories\ApprovalFactory::new();
    }
}
