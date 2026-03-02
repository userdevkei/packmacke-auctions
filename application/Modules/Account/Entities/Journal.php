<?php

namespace Modules\Account\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Journal extends Model
{
    use HasFactory, softDeletes;

    protected $keyType = 'string';
    protected $primaryKey = 'journal_id';
    protected $date = 'deleted_at';
    protected $fillable = ['journal_id', 'invoice_id', 'account_id', 'debit', 'credit', 'description'];

    protected static function newFactory()
    {
        return \Modules\Accounts\Database\factories\JournalFactory::new();
    }
}
