<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
{
    use HasFactory, HasUuids;
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $fillable = [
        'id', 'sender_id', 'recipient_id', 'type','unread_messages'
    ];

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function recipient()
    {
        return $this->morphTo();
    }
    public function messages()
    {
        return $this->belongsToMany(Message::class, 'conversation_messages');
    }

}
