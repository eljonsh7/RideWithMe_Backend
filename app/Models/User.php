<?php

namespace App\Models;

use Laravel\Sanctum\HasApiTokens;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class User extends Authenticatable
{
    use HasFactory,HasApiTokens;

    protected $primaryKey = 'id';
    public $incrementing = false;

    protected $hidden = [
        'password',
    ];

    protected $fillable = [
        'id', 'first_name', 'last_name', 'profile_picture', 'email', 'password', 'role',
    ];

    public function friends()
    {
        return $this->belongsToMany(User::class, 'friends', 'user_id', 'friend_id');
    }

    public function userCar() {
        return $this->hasMany(UserCar::class, 'user_id');
    }
}
