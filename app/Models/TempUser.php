<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;

class TempUser extends Model
{
    use HasFactory,HasApiTokens;

    protected $primaryKey = 'id';
    public $incrementing = false;

    protected $fillable = [
        'id', 'first_name', 'last_name', 'email', 'password', 'role','token'
    ];
}
