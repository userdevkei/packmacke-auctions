<?php

namespace Modules\Account\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Invoice extends Model
{
    use HasFactory, softDeletes;

    protected $fillable = ['invoice_id', 'invoice_number', 'client_id', 'date_invoiced', 'due_date', 'financial_year_id', 'customer_message', 'status', 'user_id', 'amount_due', 'posted', 'kra_number', 'destination_id', 'si_number', 'container_type', 'type', 'consignee', 'inv_reference'];
    protected $dates = ['deleted_at'];
    protected $primaryKey = 'invoice_id';
    protected $keyType = 'string';

    public static function newInvNumber()
    {
        $year = date('y'); // Get the last two digits of the year
        $prefix = 'INV';
        $newID = null;

        DB::beginTransaction();

        try {
            // Get the maximum existing serialized number
            $lastID = self::withTrashed()
                ->where('invoice_number', 'like', $prefix . '%-' . $year)
                ->orderBy('invoice_number', 'desc')
                ->lockForUpdate()
                ->first();

            $lastSerialNumber = $lastID ? intval(substr($lastID->invoice_number, strlen($prefix), strpos($lastID->invoice_number, '-') - strlen($prefix))) : 0;

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

    public static function newCreditNote()
    {
        $year = date('y'); // Get the last two digits of the year
        $prefix = 'CRN';
        $newID = null;

        DB::beginTransaction();

        try {
            // Get the maximum existing serialized number
            $lastID = self::withTrashed()
                ->where('invoice_number', 'like', $prefix . '%-' . $year)
                ->orderBy('invoice_number', 'desc')
                ->lockForUpdate()
                ->first();

            $lastSerialNumber = $lastID ? intval(substr($lastID->invoice_number, strlen($prefix), strpos($lastID->invoice_number, '-') - strlen($prefix))) : 0;

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

    protected static function newFactory()
    {
        return \Modules\Accounts\Database\factories\InvoiceFactory::new();
    }


}
