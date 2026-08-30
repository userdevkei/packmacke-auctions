<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Clerk\Entities\WarehouseLocation;
use Modules\Inventory\Entities\Requisition;

class Station extends Model
{

    protected $primaryKey = 'station_id';
    protected $keyType = 'string';

    protected $fillable = ['station_id', 'station_name', 'status', 'capacity', 'address', 'created_by', 'updated_by', 'location_id'];

    public function bays()
    {
        return $this->hasMany(WarehouseBay::class, 'station_id', 'station_id');

    }
    public function location(){
        return $this->belongsTo(WarehouseLocation::class, 'location_id', 'location_id');
    }

    public function requisitions()
    {
        return $this->hasMany(Requisition::class, 'warehouse_id', 'station_id');
    }
}
