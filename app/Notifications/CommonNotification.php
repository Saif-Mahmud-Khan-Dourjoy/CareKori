<?php

namespace App\Notifications;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CommonNotification extends Notification
{
    use Queueable;

    protected $source;




    protected $title;

    protected $message;



    public function __construct($source,  $title, $message)
    {
        $this->source = $source;

        $this->title = $title;
        $this->message = $message;
    }


    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toDatabase($notifiable)
    {
        return [
            'title'   => "{$this->title}",
            'message' => "{$this->message}",

        ];
    }

    public function toBroadcast($notifiable)
    {
        return new BroadcastMessage([
            'title'   => "{$this->title}",
            'message' => "{$this->message}",

        ]);
    }

    public function broadcastOn()
    {
        return new PrivateChannel('commonNotification.' . $this->source->unique_user_id);
    }


    public function broadcastAs()
    {
        return 'CommonNotification';
    }
}
