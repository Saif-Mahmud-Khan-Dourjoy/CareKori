<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class PrivateNotificationEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public $message;
    public $uniqueUserId;

    public function __construct($message, $uniqueUserId)
    {
        $this->message = $message;
        $this->uniqueUserId = $uniqueUserId;
    }

    public function broadcastOn()
    {
        Log::info('ProviderRegisteredEvent broadcastOn() called');
        return new PrivateChannel('private-user.' . $this->uniqueUserId);
    }

    public function broadcastAs()
    {
        return 'PrivateNotificationEvent';
    }
}