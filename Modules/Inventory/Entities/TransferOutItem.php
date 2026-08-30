<?php

namespace Modules\Inventory\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TransferOutItem extends Model
{
    protected $fillable = [
        'id', 'transfer_out_id', 'item_id', 'quantity'
    ];

    public function transferOut()
    {
        return $this->belongsTo(TransferOut::class, 'transfer_out_id');
    }

    public function item()
    {
        return $this->belongsTo(InventoryItem::class, 'item_id');
    }
}
