<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Rating extends Model
{
    use HasFactory;
    protected $primaryKey = 'id';

    protected $fillable = [
        'id', 'rated_user_id', 'rater_id',
    ];

    public function ratedUser()
    {
        return $this->belongsTo(User::class, 'rated_user_id');
    }

    public function rater()
    {
        return $this->belongsTo(User::class, 'rater_id');
    }
}
