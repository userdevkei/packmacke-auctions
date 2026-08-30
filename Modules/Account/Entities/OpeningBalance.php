<?php

namespace Modules\Account\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class OpeningBalance extends Model
{
    use HasFactory, SoftDeletes;
    protected  $primaryKey = 'opening_id';
    protected  $keyType = 'string';
    protected  $date = 'deleted_at';
    protected $fillable = ['opening_balance_id', 'client_id', 'financial_year_id', 'amount', 'type', 'date_invoiced', 'user_id', 'ledger_id'];

    protected static function newFactory()
    {
        return \Modules\Account\Database\factories\OpeningBalanceFactory::new();
    }
}
