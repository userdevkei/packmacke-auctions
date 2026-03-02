<?php

namespace Modules\Inventory\Entities;

use App\Models\UserInfo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LpoStatusHistory extends Model
{
    protected $table = 'lpo_status_histories';
    protected $primaryKey = 'id';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = [
        'id',
        'lpo_id',
        'previous_status',
        'new_status',
        'changed_by',
        'changed_at',
        'remarks',
    ];

    protected $casts = [
        'changed_at' => 'datetime',
    ];

    /**
     * Relationships
     */
    public function lpo()
    {
        return $this->belongsTo(LocalPurchaseOrder::class, 'lpo_id', 'id');
    }

    public function changedBy()
    {
        return $this->belongsTo(UserInfo::class, 'changed_by', 'user_id');
    }

    /**
     * Accessors
     */
    public function getFormattedChangedAtAttribute()
    {
        return $this->changed_at?->format('d/m/Y H:i:s');
    }

    public function getChangedByNameAttribute()
    {
        return $this->changedBy?->name ?? 'System';
    }

    public function getStatusChangeTextAttribute()
    {
        if ($this->previous_status) {
            return "Changed from {$this->previous_status} to {$this->new_status}";
        }
        return "Set to {$this->new_status}";
    }
}
