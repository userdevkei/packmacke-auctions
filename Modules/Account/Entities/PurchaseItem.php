<?php

namespace Modules\Account\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseItem extends Model
{
    use HasFactory, softDeletes;
    protected $date = 'deleted_at';
    protected $keyType = 'string';
    protected $primaryKey = 'purchase_item_id';
    protected $fillable = ['purchase_item_id', 'purchase_id', 'tax_id', 'ledger_id', 'quantity', 'unit_price', 'tax_id', 'description', 'status'];

    protected static function newFactory()
    {
        return \Modules\Accounts\Database\factories\PurchaseItemFactory::new();
    }
}
