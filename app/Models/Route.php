<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Route extends Model
{
    use HasFactory,HasUuids;
    protected $primaryKey = 'id';

    protected $fillable = [
        'id', 'driver_id', 'city_from_id', 'city_to_id', 'location_id', 'datetime', 'passengers_number','price','created_at',
        'updated_at',
    ];

    public function driver()
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    public function cityFrom()
    {
        return $this->belongsTo(City::class, 'city_from_id');
    }

    public function cityTo()
    {
        return $this->belongsTo(City::class, 'city_to_id');
    }

    public function location()
    {
        return $this->belongsTo(Location::class, 'location_id');
    }

    public function reservations()
    {
        return $this->hasMany(Reservation::class);
    }
}
