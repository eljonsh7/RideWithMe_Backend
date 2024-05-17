<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;
    public $recipientId;

    /**
     * Create a new event instance.
     */
    public function __construct(Message $message, $recipientId)
    {
        $this->message = $message;
        $this->recipientId = $recipientId;
    }

    public function broadcastOn()
    {
        return new PrivateChannel('user.'.$this->recipientId);
    }

    public function broadcastWith()
    {
        return [
            'recipient_id' => $this->recipientId,
            'message' => $this->message,

        ];
    }

    public function broadcastAs()
    {
        return 'NewMessage';
    }
}
