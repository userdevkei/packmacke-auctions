<?php

namespace Modules\Inventory\Entities;

use App\Models\Client;
use App\Models\UserInfo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Release extends Model
{
    use SoftDeletes;

    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = ['id', 'release_number', 'release_date', 'client_id', 'released_to', 'notes', 'status', 'approved_by', 'user_id', 'driver_name', 'phone_number', 'registration_number'];

    public function items()
    {
        return $this->hasMany(ReleaseItem::class, 'release_id');
    }

    public function client()
    {
        return $this->belongsTo(Client::class, 'client_id', 'client_id');
    }

    public function user()
    {
        return $this->belongsTo(UserInfo::class, 'user_id', 'user_id');
    }

    public function approvedBy()
    {
        return $this->belongsTo(UserInfo::class, 'approved_by', 'user_id');
    }
    public static function generateNumber()
    {
        $last = self::orderBy('created_at', 'desc')->first();
        $number = $last ? intval(substr($last->release_number, -4)) + 1 : 1;
        return 'RE-' . date('y') . str_pad($number, 4, '0', STR_PAD_LEFT);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'item_id', 'id');
    }
}
