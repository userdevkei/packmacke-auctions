<?php

namespace Modules\Admin\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class OtherTransporter extends Model
{
    use HasFactory, softDeletes;
    protected $primaryKey = 'transporter_id';
    protected $keyType = 'string';
    protected $fillable = ['transporter_id', 'transporter_name'];

    protected static function newFactory()
    {
        return \Modules\Admin\Database\factories\OtherTransporterFactory::new();
    }
}
