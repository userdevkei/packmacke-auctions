<?php

namespace Modules\Tasks\Entities;

use App\Models\UserInfo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class NotificationUser extends Model
{
    use HasFactory, SoftDeletes;

    protected $primaryKey = 'id';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $dates = ['deleted_at'];

    protected $fillable = [
        'id', 'notification_id', 'user_id', 'is_read', 'read_at'
    ];

    public function notification()
    {
        return $this->belongsTo(Notification::class);
    }

    public function user()
    {
        return $this->belongsTo(UserInfo::class, 'user_id', 'user_id');
    }

    protected static function newFactory()
    {
        return \Modules\Tasks\Database\factories\NotificationUserFactory::new();
    }
}
