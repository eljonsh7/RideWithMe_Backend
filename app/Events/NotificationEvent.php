<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NotificationEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $notificationEventData;

    public function __construct($notificationEventData)
    {
        $this->notificationEventData = $notificationEventData;
    }

    public function broadcastOn()
    {

        return new PrivateChannel('user.'.$this->notificationEventData['user_id']);
    }

    public function broadcastAs()
    {
        return "NotificationEvent";
    }

}
