<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Suggestion extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'id','user_id', 'type', 'content'
    ];

    public $incrementing = false;

    public function user()
    {
        return $this->belongsTo(User::class,'user_id');
    }
}
