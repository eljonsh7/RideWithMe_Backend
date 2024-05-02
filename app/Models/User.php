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

    protected $fillable = [
        'id', 'first_name', 'last_name', 'profile_picture', 'email', 'password', 'role',
    ];
}
