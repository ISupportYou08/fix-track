<?php

namespace App\Notifications;

use App\Models\TechnicianVerification;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class TechnicianApplicationDecisionNotification extends Notification
{
    use Queueable;

    public function __construct(public TechnicianVerification $verification) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $approved = $this->verification->status === 'approved';
        $mail = (new MailMessage)
            ->subject($approved ? 'Your FixTrack technician account is approved' : 'Update on your FixTrack technician application')
            ->greeting('Hello '.$notifiable->name.',')
            ->line($approved
                ? 'Your technician application has been approved and your account is now active.'
                : 'Your technician application needs changes before it can be activated.');

        if (! $approved && filled($this->verification->decision_reason)) {
            $mail->line('Issue found: '.$this->verification->decision_reason);
        }

        return $mail
            ->action($approved ? 'Open technician dashboard' : 'Review and resubmit application', route('technician.module'))
            ->line($approved ? 'You can now receive and manage service jobs.' : 'Sign in to correct the application and submit it for another review.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'verification_id' => $this->verification->id,
            'status' => $this->verification->status,
            'title' => 'Technician application '.Str::headline((string) $this->verification->status),
            'detail' => $this->verification->decision_reason,
        ];
    }
}
