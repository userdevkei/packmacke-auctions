<?php

namespace Modules\Clerk\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Rebagging extends Model
{
    use HasFactory, softDeletes;
    protected $primaryKey = 'rebagging_id';
    protected $keyType = 'string';
    protected $date = 'deleted_at';
    protected $fillable = ['rebagging_id', 'shipping_id', 'stock_id', 'packages', 'weight'];

    protected static function newFactory()
    {
        return \Modules\Clerk\Database\factories\RebaggingFactory::new();
    }
}
