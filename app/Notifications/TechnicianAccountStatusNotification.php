<?php

namespace App\Notifications;

use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TechnicianAccountStatusNotification extends Notification
{
    use Queueable;

    public function __construct(public string $status, public string $reason, public ?CarbonInterface $suspendedUntil = null) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(User $notifiable): MailMessage
    {
        $action = match ($this->status) {
            'suspended' => 'suspended',
            'banned' => 'banned',
            default => 'restored',
        };

        $mail = (new MailMessage)
            ->subject('Your FixTrack technician account has been '.$action)
            ->greeting('Hello '.$notifiable->name.',')
            ->line('Your technician account has been '.$action.'.')
            ->line('Staff message: '.$this->reason);

        if ($this->status === 'suspended' && $this->suspendedUntil !== null) {
            $mail->line('Your suspension ends on '.$this->suspendedUntil->format('M j, Y g:i A T').'.');
        }

        return $mail->line($this->status === 'active'
                ? 'You can sign in again and manage service jobs.'
                : 'You cannot access the technician workspace while this account action is in effect.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(User $notifiable): array
    {
        return [
            'status' => $this->status,
            'title' => 'Technician account '.($this->status === 'active' ? 'restored' : $this->status),
            'detail' => $this->reason,
            'suspended_until' => $this->suspendedUntil?->toIso8601String(),
        ];
    }
}
