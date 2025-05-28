<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Support\Facades\Log;

class ProviderRegisteredNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $provider;
    protected $targetUser;


    public function __construct($provider, $targetUser)
    {
        $this->provider = $provider;
        $this->targetUser = $targetUser;
    }

    public function via($notifiable)
    {
        return ['database', 'broadcast'];
    }

    public function toDatabase($notifiable)
    {
        return [
            'title' => 'New Provider Registered',
            'message' => "{$this->provider->name} has joined as a provider.",
            'type' => 'provider_registered',
        ];
    }

    public function toBroadcast($notifiable)
    {
        return new BroadcastMessage([
            'title' => 'New Provider Registered',
            'message' => "{$this->provider->name} has joined as a provider.",
            'type' => 'provider_registered',
        ]);
    }


    public function broadcastOn(): array
    {
        return ['user.' . $this->targetUser->unique_user_id];
    }

    public function broadcastAs()
    {
        return 'ProviderRegisteredNotification';
    }
}
