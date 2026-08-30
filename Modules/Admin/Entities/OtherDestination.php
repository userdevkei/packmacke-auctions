<?php

namespace Modules\Admin\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class OtherDestination extends Model
{
    use HasFactory, softDeletes;
    protected $primaryKey = 'warehouse_id';
    protected $keyType = 'string';
    protected $fillable = ['warehouse_id', 'warehouse_name'];

    protected static function newFactory()
    {
        return \Modules\Admin\Database\factories\OtherDestinationFactory::new();
    }
}
