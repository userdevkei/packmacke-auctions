<?php

namespace Modules\Inventory\Entities;

use App\Models\Client;
use App\Models\UserInfo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class TransferOut extends Model
{
    use SoftDeletes;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id', 'transfer_out_number', 'transfer_date', 'client_id',
        'recipient_id', 'notes', 'status', 'approved_by', 'user_id'
    ];

    public function items()
    {
        return $this->hasMany(TransferOutItem::class, 'transfer_out_id', 'id');
    }

    public function client()
    {
        return $this->belongsTo(Client::class, 'client_id', 'client_id');
    }

    public function recipient()
    {
        return $this->belongsTo(Client::class, 'recipient_id', 'client_id');
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
        $number = $last ? intval(substr($last->transfer_out_number, -4)) + 1 : 1;
        return 'TT' . date('y') . str_pad($number, 4, '0', STR_PAD_LEFT);
    }
    public function item()
    {
        return $this->belongsTo(InventoryItem::class, 'item_id', 'id');
    }
}
