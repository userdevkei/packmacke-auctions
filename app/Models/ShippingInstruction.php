<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ShippingInstruction extends Model
{
    use softDeletes;

    protected $fillable = ['shipping_id', 'client_id', 'vessel_id', 'destination_id', 'load_type', 'container_size', 'consignee', 'shipping_mark', 'shipping_instructions', 'shipping_number','station_id' , 'status', 'user_id', 'address', 'booking_number', 'si_number', 'container_number', 'container_tare', 'clearing_agent', 'seal_number', 'escort', 'transporter_id', 'registration', 'driver_id', 'ship_date', 'invoice_number'];

    protected $primaryKey = 'shipping_id';
    protected $keyType = 'string';
//
    protected $date = 'deleted_at';

    protected $casts = [
        'address' => 'array'
    ];
}
