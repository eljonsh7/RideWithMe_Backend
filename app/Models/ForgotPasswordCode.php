<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ForgotPasswordCode extends Model
{
    protected $fillable = [
        'email', 'code', 'expiration_time', 'created_at'
    ];
    public $incrementing = false;

    protected $keyType = 'string';

    public function user()
    {
        return $this->belongsTo(User::class, 'email', 'email');
    }
}
