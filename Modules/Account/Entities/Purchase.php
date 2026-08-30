<?php

namespace Modules\Account\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Purchase extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['purchase_id', 'invoice_number', 'voucher_number', 'client_id', 'tax_id', 'date_invoiced', 'due_date', 'financial_year_id', 'customer_message', 'status', 'user_id', 'amount_due', 'kra_number', 'posted', 'type', 'inv_reference'];
    protected $date = 'deleted_at';
    protected $primaryKey = 'purchase_id';
    protected $keyType = 'string';

    public static function newPINumber()
    {
        $year = date('y');
        $prefix = 'PI';
        $newID = null;
        DB::beginTransaction();
        try {
            $lastID = self::withTrashed()
                ->where('voucher_number', 'like', $prefix . '%-' . $year)
                ->orderBy('voucher_number', 'desc')
                ->lockForUpdate() // Lock the rows to prevent concurrent access
                ->first();

            $lastSerialNumber = $lastID ? intval(substr($lastID->voucher_number, strlen($prefix), strpos($lastID->voucher_number, '-') - strlen($prefix))) : 0;
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

    public static function newDebitNote()
    {
        $year = date('y');
        $prefix = 'DRN';
        $newID = null;
        DB::beginTransaction();
        try {
            $lastID = self::withTrashed()
                ->where('voucher_number', 'like', $prefix . '%-' . $year)
                ->orderBy('voucher_number', 'desc')
                ->lockForUpdate() // Lock the rows to prevent concurrent access
                ->first();

            $lastSerialNumber = $lastID ? intval(substr($lastID->voucher_number, strlen($prefix), strpos($lastID->voucher_number, '-') - strlen($prefix))) : 0;
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
        return \Modules\Accounts\Database\factories\PurchaseFactory::new();
    }
}
