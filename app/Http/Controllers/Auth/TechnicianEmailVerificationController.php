<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\SendTechnicianEmailVerificationCode;
use App\Http\Controllers\Controller;
use App\Http\Requests\VerifyTechnicianEmailOtpRequest;
use App\Models\EmailVerificationCode;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class TechnicianEmailVerificationController extends Controller
{
    public function show(Request $request): View|RedirectResponse
    {
        $technician = $this->technician($request);

        if ($technician->email_verified_at !== null) {
            return redirect()->route('technician.module');
        }

        return view('pages.auth.verify-technician-email', ['technician' => $technician]);
    }

    public function verify(VerifyTechnicianEmailOtpRequest $request): RedirectResponse
    {
        $technician = $this->technician($request);

        if ($technician->email_verified_at !== null) {
            return redirect()->route('technician.module');
        }

        $result = DB::transaction(function () use ($request, $technician): string {
            $verificationCode = EmailVerificationCode::query()
                ->whereBelongsTo($technician)
                ->lockForUpdate()
                ->first();

            if ($verificationCode === null || $verificationCode->expires_at->isPast()) {
                $verificationCode?->delete();

                return 'expired';
            }

            if ($verificationCode->attempts >= 5) {
                $verificationCode->delete();

                return 'too_many_attempts';
            }

            if (! Hash::check($request->validated('code'), $verificationCode->code_hash)) {
                $verificationCode->increment('attempts');

                return 'incorrect';
            }

            $technician->forceFill([
                'email_verified_at' => now(),
                'account_status' => User::ACCOUNT_REVIEW_PENDING,
            ])->save();
            $verificationCode->delete();

            return 'verified';
        });

        if ($result !== 'verified') {
            $message = match ($result) {
                'expired' => 'This code has expired. Request a new code.',
                'too_many_attempts' => 'Too many incorrect attempts. Request a new code.',
                default => 'The verification code is incorrect.',
            };

            throw ValidationException::withMessages(['code' => $message]);
        }

        return redirect()->route('technician.module')->with('status', 'Email verified. Your application is now waiting for staff review.');
    }

    public function resend(Request $request, SendTechnicianEmailVerificationCode $sender): RedirectResponse
    {
        $technician = $this->technician($request);

        if ($technician->email_verified_at !== null) {
            return redirect()->route('technician.module');
        }

        try {
            $sender->send($technician);
        } catch (\Throwable $exception) {
            report($exception);

            return back()->withErrors([
                'email' => 'The verification email could not be delivered. Ask the system administrator to configure the application mail service.',
            ]);
        }

        return back()->with('status', 'A new verification code was sent to your email.');
    }

    private function technician(Request $request): User
    {
        $user = $request->user();
        abort_unless($user instanceof User && $user->isTechnician(), 403);

        return $user;
    }
}
