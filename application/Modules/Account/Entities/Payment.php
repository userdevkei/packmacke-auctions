<?php

namespace Modules\Account\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Payment extends Model
{
    use HasFactory, softDeletes;
    protected $primaryKey = 'payment_id';
    protected $keyType = 'string';
    protected $date = 'deleted_at';
    protected $fillable = ['payment_id', 'invoice_number', 'client_id', 'financial_year_id', 'account_id', 'date_received', 'amount_received', 'transaction_code', 'description', 'user_id', 'status', 'bank_date', 'reconciled', 'exchange_rate', 'si_number', 'exchange_rate'];
    public static function newPayInvNumber()
    {
        $year = date('y');
        $prefix = 'P';
        $newID = null;

        // Start a transaction
        DB::beginTransaction();

        try {
            $lastID = self::withTrashed()
                ->where('invoice_number', 'like', $prefix . '%-' . $year)
                ->orderBy('invoice_number', 'desc')
                ->lockForUpdate() // Lock the rows to prevent concurrent access
                ->first();
            $lastSerialNumber = $lastID ? intval(substr($lastID->invoice_number, strlen($prefix), strpos($lastID->invoice_number, '-') - strlen($prefix))) : 0;
            $serialNumber = $lastSerialNumber + 1;
            $newID = $prefix . str_pad($serialNumber, 5, '0', STR_PAD_LEFT) . '-' . $year;
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
        return \Modules\Account\Database\factories\PaymentFactory::new();
    }
}
