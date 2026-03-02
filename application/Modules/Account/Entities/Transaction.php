<?php

namespace Modules\Account\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Transaction extends Model
{
    use HasFactory, softDeletes;
    protected $keyType = 'string';
    protected $primaryKey = 'transaction_id';
    protected $date = 'deleted_at';
    protected $fillable = ['transaction_id', 'invoice_number', 'client_id', 'date_received', 'amount_received', 'financial_year_id', 'description', 'user_id', 'transaction_code', 'account_id', 'status', 'reconciled', 'bank_date', 'si_number', 'exchange_rate'];

    public static function newPayInvNumber()
    {
        $year = date('y');
        $prefix = 'R';
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
            $newID = $prefix . str_pad($serialNumber, 4, '0', STR_PAD_LEFT) . '-' . $year;
            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
        }
        return $newID;
    }
    protected static function newFactory()
    {
        return \Modules\Accounts\Database\factories\TransactionFactory::new();
    }
}
