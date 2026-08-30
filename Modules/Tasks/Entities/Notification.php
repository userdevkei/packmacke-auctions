<?php

namespace Modules\Tasks\Entities;

use App\Models\UserInfo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Notification extends Model
{
    use HasFactory, SoftDeletes;
    protected $primaryKey = 'id';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $dates = ['deleted_at'];

    protected $fillable = [
       'id', 'type', 'title', 'message', 'data', 'created_by'
    ];

    protected $casts = [
        'data' => 'array'
    ];

    public function users()
    {
        return $this->hasMany(NotificationUser::class);
    }

    public function creator()
    {
        return $this->belongsTo(UserInfo::class, 'created_by', 'user_id');
    }

    protected static function newFactory()
    {
        return \Modules\Tasks\Database\factories\NotificationFactory::new();
    }
}
