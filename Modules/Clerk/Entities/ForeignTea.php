<?php

namespace Modules\Clerk\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class ForeignTea extends Model
{
    use HasFactory, SoftDeletes;

    protected $primaryKey = 'foreign_teas_id';
    protected $keyType = 'string';
    protected $fillable = ['foreign_teas_id', 'received', 'validated', 'delivery_order_id'];

    protected static function newFactory()
    {
        return \Modules\Clerk\Database\factories\ForeignTeaFactory::new();
    }
}
