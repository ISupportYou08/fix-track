<?php

namespace App\Actions\Auth;

use App\Models\User;
use App\Notifications\TechnicianEmailVerificationCodeNotification;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class SendTechnicianEmailVerificationCode
{
    public function send(User $technician): void
    {
        abort_unless($technician->isTechnician(), 422, 'Email codes are only available for technician accounts.');

        if (in_array(config('mail.default'), ['array', 'log'], true)) {
            throw new RuntimeException('Inbox email delivery is not configured.');
        }

        $code = (string) random_int(100000, 999999);

        $technician->emailVerificationCode()->updateOrCreate([], [
            'code_hash' => Hash::make($code),
            'attempts' => 0,
            'expires_at' => now()->addMinutes(10),
        ]);

        $technician->notify(new TechnicianEmailVerificationCodeNotification($code));
    }
}
