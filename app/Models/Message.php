<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    use HasFactory;
    protected $primaryKey = 'id';

    protected $fillable = [
        'id', 'user_id', 'content', 'type',
    ];
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}