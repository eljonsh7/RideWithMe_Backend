<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Suggestion extends Model
{
    protected $fillable = [
        'user_id', 'type', 'content'
    ];

    public $incrementing = false;

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
