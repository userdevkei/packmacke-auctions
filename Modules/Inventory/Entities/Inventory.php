<?php

namespace Modules\Inventory\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Inventory extends Model
{
    use SoftDeletes;

    protected $fillable = ['id', 'purchase_order_id', 'item_id', 'quantity'];
    protected $keyType = 'string';
    public $incrementing = false;

    public function uom(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'item_id', 'id');
    }

}
