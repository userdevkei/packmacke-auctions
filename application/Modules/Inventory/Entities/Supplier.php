<?php

namespace Modules\Inventory\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Supplier extends Model
{
    use SoftDeletes;

    protected $fillable = ['id', 'supplier_name', 'town', 'phone_number', 'street', 'notes', 'email', 'po_box'];
    protected $keyType = 'string';
    public $incrementing = false;

    public function purchaseOrders()
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    public function localPurchaseOrders(){
        return $this->hasMany(LocalPurchaseOrder::class);
    }
}
