<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Group extends Model
{
    use HasFactory, HasUuids;

    public $incrementing = false;
    protected $fillable = [
        'id', 'route_id', 'group_picture','status'
    ];

    public function route()
    {
        return $this->belongsTo(Route::class, 'route_id');
    }

}
