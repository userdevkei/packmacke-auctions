<?php

namespace Modules\Clerk\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DeliveryNote extends Model
{
    use HasFactory;

    protected $fillable = ['path', 'delivery_number'];

    protected static function newFactory()
    {
        return \Modules\Clerk\Database\factories\DeliveryNoteFactory::new();
    }
}
