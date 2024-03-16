<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    use HasFactory;
    protected $primaryKey = 'id';

    protected $fillable = [
        'id', 'city_id', 'name',
    ];

    public function city()
    {
        return $this->belongsTo(City::class, 'city_id');
    }
}
