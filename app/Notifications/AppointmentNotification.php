<?php

namespace App\Notifications;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AppointmentNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */

    protected $source;
    protected $target;

    protected $appointment;

    protected $status;

    public function __construct($source, $target, $appointment, $status)
    {
        $this->source = $source;
        $this->target = $target;
        $this->appointment = $appointment;
        $this->status = $status;
    }

 
    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toDatabase($notifiable)
    {
        return [
            'title'   => "Appointment {$this->status}",
            'message' => "Your appointment with {$this->target->name} at {$this->appointment->appointment_time} has been {$this->appointment->status} .",
            
        ];
    }

    public function toBroadcast($notifiable)
    {
        return new BroadcastMessage([
            'title'   => "Appointment {$this->status}",
            'message' => "Your appointment with {$this->target->name} at {$this->appointment->appointment_time} has been {$this->appointment->status} .",

        ]);
    }

    public function broadcastOn()
    {
        return new PrivateChannel('appointmentNotification.' . $this->source->unique_user_id);
    }


    public function broadcastAs()
    {
        return 'AppointmentNotification';
    }
}
