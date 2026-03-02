<?php

namespace Modules\Account\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseJournal extends Model
{
    use HasFactory, SoftDeletes;
    protected $date = 'deleted_at';
    protected $primaryKey = 'purchase_journal_id';
    protected $keyType = 'string';
    protected $fillable = ['purchase_journal_id', 'purchase_id', 'account_id', 'debit', 'credit', 'description'];

    protected static function newFactory()
    {
        return \Modules\Accounts\Database\factories\PurchaseJournalFactory::new();
    }
}
