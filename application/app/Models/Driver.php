<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Driver extends Model
{
    use HasFactory;

    protected $primaryKey = 'driver_id';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['driver_id', 'id_number', 'driver_name', 'phone'];
}
