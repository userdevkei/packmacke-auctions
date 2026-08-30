<?php

namespace Modules\Account\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class PettyCash extends Model
{
    use HasFactory, softDeletes;
    protected $primaryKey = 'petty_cash_id';
    protected $keyType = 'string';
    protected $date = 'deleted_at';

    protected $fillable = ['petty_cash_id', 'reference_code', 'ledger_id', 'amount', 'description', 'date_invoiced', 'type', 'status', 'user_id', 'si_number'];

    public static function newReferenceCode()
    {
        $year = date('ym');
        $prefix = 'PC';
        $newID = null;

        // Start a transaction
        DB::beginTransaction();

        try {
            // Get the maximum existing serialized number for the current year
            $lastID = self::withTrashed()->where('reference_code', 'like', $prefix . $year . '%')
//                ->whereNull('deleted_at')
                ->orderBy('reference_code', 'desc')
                ->lockForUpdate() // Lock the rows to prevent concurrent access
                ->first();

            $lastSerialNumber = $lastID ? intval(substr($lastID->reference_code, strlen($prefix . $year))) : 0;

            // Increment the serialized number
            $serialNumber = $lastSerialNumber + 1;

            // Generate the full identifier with leading zeros
            $newID = $prefix . $year . str_pad($serialNumber, 3, '0', STR_PAD_LEFT);

            // Commit the transaction
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
        return \Modules\Account\Database\factories\PettyCashFactory::new();
    }
}
