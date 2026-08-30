<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ShipmentContainer extends Model
{
    use HasFactory, softDeletes;

    protected $fillable = ['container_id', 'container_number', 'blend_id', 'seal_number', 'tare_weight', 'pallet_weight'];
}
