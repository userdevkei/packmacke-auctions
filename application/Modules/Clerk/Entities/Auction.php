<?php

namespace Modules\Clerk\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Auction extends Model
{
    use HasFactory, SoftDeletes;

    protected $primaryKey = 'auction_id';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $date = 'deleted_at';

    protected $fillable = ['auction_id', 'stock_id', 'delivery_id', 'broker_id', 'sale', 'warrant_number', 'status', 'user_id', 'client_id', 'warehouse_id', 'sale_date', 'release_date', 'prompt_date', 'type'];

    public static function newWarrantNumber($type)
    {
        $year = date('y');
        $prefix = $type == 'private' ? 'PPS-' : 'PAH-';
        $newID = null;

        // Start a transaction
        DB::beginTransaction();

        try {
            $lastID = self::withTrashed()
                ->where('warrant_number', 'like', $prefix . '%-' . $year)
                ->orderBy('warrant_number', 'desc')
                ->lockForUpdate() // Lock the rows to prevent concurrent access
                ->first();
            $lastSerialNumber = $lastID ? intval(substr($lastID->warrant_number, strlen($prefix), strpos($lastID->warrant_number, '-') - strlen($prefix))) : 0;
            $serialNumber = $lastSerialNumber + 1;
            $newID = $prefix . str_pad($serialNumber, 4, '0', STR_PAD_LEFT) . '-' . $year;
            DB::commit();
        } catch (\Exception $e) {
            // Rollback the transaction if an exception occurs
            DB::rollback();
            // Handle or log the exception
        }

        return $newID;
    }
    protected static function newFactory()
    {
        return \Modules\Clerk\Database\factories\AuctionFactory::new();
    }
}
