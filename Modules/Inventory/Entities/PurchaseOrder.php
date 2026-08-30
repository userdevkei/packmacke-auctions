<?php

namespace Modules\Inventory\Entities;

use App\Models\Client;
use App\Models\UserInfo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class PurchaseOrder extends Model
{
    use SoftDeletes;

    protected $primaryKey = 'id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = ['id', 'purchase_order_number','lpo_number', 'client_id', 'supplier_id', 'notes', 'status', 'date', 'user_id'];

    public static function newPurchaseOrderNumber()
    {
        $year = date('y'); // Get the last two digits of the year
        $prefix = 'PN';
        $newID = null;

        DB::beginTransaction();

        try {
            // Get the maximum existing serialized number
            $lastID = self::withTrashed()
                ->where('purchase_order_number', 'like', $prefix . '%-' . $year)
                ->orderBy('purchase_order_number', 'desc')
                ->lockForUpdate()
                ->first();

            $lastSerialNumber = $lastID ? intval(substr($lastID->purchase_order_number, strlen($prefix), strpos($lastID->purchase_order_number, '-') - strlen($prefix))) : 0;

            // Increment the serialized number
            $serialNumber = $lastSerialNumber + 1;

            // Generate the full identifier with leading zeros
            $newID = $prefix . str_pad($serialNumber, 4, '0', STR_PAD_LEFT) . '-' . $year;

            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            // Handle or log the exception
        }

        return $newID;
    }

    public function items(){
        return $this->hasMany(Inventory::class, 'purchase_order_id', 'id');
    }
    public function client(){
        return $this->belongsTo(Client::class, 'client_id', 'client_id');
    }
    public function supplier(){
        return $this->belongsTo(Supplier::class, 'supplier_id', 'id');
    }

    public function user()
    {
        return $this->belongsTo(UserInfo::class, 'user_id', 'user_id');
    }

}
