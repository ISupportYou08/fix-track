<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TechnicianEmailVerificationCodeNotification extends Notification
{
    use Queueable;

    public function __construct(public string $code, public ?string $recipientName = null) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Verify your FixTrack technician email')
            ->greeting('Hello '.($this->recipientName ?? $notifiable->name ?? 'technician').',')
            ->line('Use this one-time code to confirm your email address:')
            ->line($this->code)
            ->line('The code expires in 10 minutes. Do not share it with anyone.')
            ->line('After verification, your technician application will be sent to the FixTrack staff for review.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [];
    }
}
