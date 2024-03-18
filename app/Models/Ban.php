<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ban extends Model
{
    use HasFactory;
    protected $primaryKey = 'id';

    protected $fillable = [
        'id', 'user_id', 'date_until',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
