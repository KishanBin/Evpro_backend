<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EVStation extends Model
{
    protected $table = 'ev_station';

    protected $fillable = [
        'name',
        'location',
        'latitude',
        'longitude',
        'charging_type',
        'number_of_ports',
        'availability_status',
        'operating_hours',
        'price_per_kwh',
    ];
}
