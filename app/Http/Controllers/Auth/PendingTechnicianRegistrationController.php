<?php

namespace App\Http\Controllers\Auth;

use App\Concerns\PasswordValidationRules;
use App\Http\Controllers\Controller;
use App\Models\PendingTechnicianRegistration;
use App\Models\ServiceCatalog;
use App\Models\User;
use App\Notifications\TechnicianEmailVerificationCodeNotification;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use RuntimeException;
use Throwable;

class PendingTechnicianRegistrationController extends Controller
{
    use PasswordValidationRules;

    public function store(Request $request): RedirectResponse
    {
        $input = $request->all();
        $input['name'] = Str::of(implode(' ', array_filter([
            $input['first_name'] ?? null,
            $input['middle_name'] ?? null,
            $input['surname'] ?? null,
        ])))->squish()->toString();

        if (! array_key_exists('service_categories', $input) && isset($input['service_category'])) {
            $input['service_categories'] = [$input['service_category']];
        }

        $serviceCodes = ServiceCatalog::activeCodes();
        $validated = Validator::make($input, [
            'role' => ['required', Rule::in(['technician'])],
            'name' => ['required', 'string', 'max:255'],
            'first_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'surname' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique(User::class)],
            'password' => $this->passwordRules(),
            'phone' => ['required', 'string', 'max:30'],
            'service_category' => ['nullable', 'string', Rule::in($serviceCodes)],
            'service_categories' => ['required', 'array', 'min:1'],
            'service_categories.*' => ['string', Rule::in($serviceCodes)],
            'service_area' => ['required', 'string', 'max:255'],
            'service_type' => ['required', Rule::in(['home', 'walkin', 'both'])],
            'shop_name' => [
                'nullable',
                Rule::requiredIf(fn (): bool => in_array($input['service_type'] ?? null, ['walkin', 'both'], true)),
                'string',
                'max:255',
            ],
            'years_experience' => ['required', 'integer', 'min:0', 'max:60'],
            'valid_id' => ['required', 'file', 'mimes:jpg,jpeg,png,webp', 'mimetypes:image/jpeg,image/png,image/webp', 'max:5120'],
            'credentials' => ['required', 'file', 'mimes:pdf,doc,docx', 'mimetypes:application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'max:5120'],
            'profile_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ], $this->documentMessages())->validate();

        $previous = $this->pending($request);
        if ($previous !== null) {
            $this->discard($previous);
        }

        $code = (string) random_int(100000, 999999);
        $pending = PendingTechnicianRegistration::create([
            'email' => $validated['email'],
            'payload' => [],
            'code_hash' => Hash::make($code),
            'expires_at' => now()->addMinutes(10),
            'registration_expires_at' => now()->addDay(),
        ]);

        $storedPaths = [];

        try {
            foreach (['valid_id', 'credentials', 'profile_photo'] as $field) {
                if (! isset($validated[$field])) {
                    continue;
                }

                $path = $validated[$field]->store("technician-documents/{$pending->id}", 'local');
                if (! is_string($path)) {
                    throw new RuntimeException("Unable to store {$field}.");
                }

                $storedPaths[$field] = $path;
            }

            $pending->update([
                'payload' => [
                    'name' => $validated['name'],
                    'first_name' => $validated['first_name'],
                    'middle_name' => $validated['middle_name'] ?? null,
                    'surname' => $validated['surname'],
                    'password' => Hash::make($validated['password']),
                    'phone' => $validated['phone'],
                    'service_categories' => ServiceCatalog::normalizeCodes($validated['service_categories']),
                    'service_area' => $validated['service_area'],
                    'service_type' => $validated['service_type'],
                    'shop_name' => $validated['shop_name'] ?? null,
                    'years_experience' => (int) $validated['years_experience'],
                    'paths' => $storedPaths,
                    'labels' => [
                        'valid_id' => $validated['valid_id']->getClientOriginalName(),
                        'credentials' => $validated['credentials']->getClientOriginalName(),
                    ],
                    'profile_photo_extension' => isset($validated['profile_photo']) ? $validated['profile_photo']->extension() : null,
                ],
            ]);
        } catch (Throwable $exception) {
            Storage::disk('local')->delete(array_values($storedPaths));
            $pending->delete();

            throw $exception;
        }

        $request->session()->put('pending_technician_registration', $pending->id);

        try {
            $this->notify($pending, $code);
        } catch (Throwable $exception) {
            report($exception);

            return redirect()->route('technician.registration.show')
                ->with('email_delivery_error', 'The verification email could not be delivered. Check the mail settings, then request a new code or change your email.');
        }

        return redirect()->route('technician.registration.show')->with('status', 'A verification code was sent to your email.');
    }

    public function show(Request $request): View|RedirectResponse
    {
        $pending = $this->pending($request);
        if ($pending === null) {
            return redirect()->route('register');
        }

        return view('pages.auth.verify-technician-email', ['technician' => $pending, 'pendingRegistration' => true]);
    }

    public function verify(Request $request): RedirectResponse
    {
        $validated = $request->validate(['code' => ['required', 'digits:6']]);
        $pending = $this->pending($request);
        if ($pending === null) {
            return redirect()->route('register');
        }

        $avatarPath = null;

        try {
            $result = DB::transaction(function () use ($pending, $validated, &$avatarPath): User|string {
                $registration = PendingTechnicianRegistration::query()->lockForUpdate()->find($pending->id);
                if ($registration === null || $registration->expires_at->isPast()) {
                    return 'expired';
                }

                if ($registration->attempts >= 5) {
                    return 'too_many_attempts';
                }

                if (! Hash::check($validated['code'], $registration->code_hash)) {
                    $registration->increment('attempts');

                    return 'incorrect';
                }

                if (User::query()->where('email', $registration->email)->exists()) {
                    return 'email_taken';
                }

                $payload = $registration->payload;
                if (isset($payload['paths']['profile_photo'])) {
                    $avatarPath = 'avatars/'.Str::uuid().'.'.$payload['profile_photo_extension'];
                    $stream = Storage::disk('local')->readStream($payload['paths']['profile_photo']);
                    if (! is_resource($stream)) {
                        throw new RuntimeException('The profile photo could not be read.');
                    }

                    try {
                        if (! Storage::disk('public')->put($avatarPath, $stream)) {
                            throw new RuntimeException('The profile photo could not be saved.');
                        }
                    } finally {
                        fclose($stream);
                    }
                }

                foreach (['valid_id', 'credentials'] as $field) {
                    if (! Storage::disk('local')->exists($payload['paths'][$field])) {
                        throw new RuntimeException("The {$field} document is missing. Please register again.");
                    }
                }

                $user = User::create([
                    'name' => $payload['name'],
                    'first_name' => $payload['first_name'],
                    'middle_name' => $payload['middle_name'],
                    'surname' => $payload['surname'],
                    'email' => $registration->email,
                    'password' => $payload['password'],
                    'role' => 'technician',
                    'phone' => $payload['phone'],
                    'avatar_path' => $avatarPath,
                    'account_status' => User::ACCOUNT_REVIEW_PENDING,
                ]);
                $user->forceFill(['email_verified_at' => now()])->save();

                $verification = $user->technicianVerification()->create([
                    'status' => 'submitted',
                    'risk_level' => 'low',
                    'service_categories' => $payload['service_categories'],
                    'years_experience' => $payload['years_experience'],
                    'service_area' => $payload['service_area'],
                    'service_type' => $payload['service_type'],
                    'shop_name' => $payload['shop_name'],
                    'phone' => $payload['phone'],
                    'address' => $payload['service_area'],
                    'submitted_at' => now(),
                ]);

                foreach (['valid_id', 'credentials'] as $field) {
                    $verification->documents()->create([
                        'type' => $field,
                        'label' => $payload['labels'][$field],
                        'file_path' => $payload['paths'][$field],
                        'status' => 'pending',
                    ]);
                }

                $registration->delete();

                return $user;
            });
        } catch (Throwable $exception) {
            if ($avatarPath !== null) {
                Storage::disk('public')->delete($avatarPath);
            }

            throw $exception;
        }

        if (is_string($result)) {
            throw ValidationException::withMessages(['code' => match ($result) {
                'expired' => 'This code has expired. Request a new code.',
                'too_many_attempts' => 'Too many incorrect attempts. Request a new code.',
                'email_taken' => 'This email is already registered. Change the email address and request a new code.',
                default => 'The verification code is incorrect.',
            }]);
        }

        if (isset($pending->payload['paths']['profile_photo'])) {
            Storage::disk('local')->delete($pending->payload['paths']['profile_photo']);
        }

        $request->session()->forget('pending_technician_registration');
        Auth::login($result);
        $request->session()->regenerate();
        event(new Registered($result));

        return redirect()->route('technician.module')->with('status', 'Email verified. Your application is now waiting for staff review.');
    }

    public function resend(Request $request): RedirectResponse
    {
        $pending = $this->pending($request);
        if ($pending === null) {
            return redirect()->route('register');
        }

        return $this->refreshCode($pending);
    }

    public function updateEmail(Request $request): RedirectResponse
    {
        $pending = $this->pending($request);
        if ($pending === null) {
            return redirect()->route('register');
        }

        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255', Rule::unique(User::class)],
        ]);

        return $this->refreshCode($pending, $validated['email']);
    }

    private function refreshCode(PendingTechnicianRegistration $pending, ?string $email = null): RedirectResponse
    {
        $previous = $pending->only(['email', 'code_hash', 'attempts', 'expires_at']);
        $code = (string) random_int(100000, 999999);
        $pending->update([
            'email' => $email ?? $pending->email,
            'code_hash' => Hash::make($code),
            'attempts' => 0,
            'expires_at' => now()->addMinutes(10),
        ]);

        try {
            $this->notify($pending, $code);
        } catch (Throwable $exception) {
            $pending->update($previous);
            report($exception);

            return back()->withErrors(['email' => 'The verification email could not be delivered. Please try again later.']);
        }

        return back()->with('status', 'A new verification code was sent to '.$pending->email.'.');
    }

    private function notify(PendingTechnicianRegistration $pending, string $code): void
    {
        if (in_array(config('mail.default'), ['array', 'log'], true)) {
            throw new RuntimeException('Inbox email delivery is not configured.');
        }

        Notification::route('mail', [$pending->email => $pending->payload['name']])
            ->notify(new TechnicianEmailVerificationCodeNotification($code, $pending->payload['name']));
    }

    private function pending(Request $request): ?PendingTechnicianRegistration
    {
        $id = $request->session()->get('pending_technician_registration');

        $pending = is_string($id) ? PendingTechnicianRegistration::query()->find($id) : null;

        if ($pending !== null && $pending->registration_expires_at->isPast()) {
            $this->discard($pending);
            $request->session()->forget('pending_technician_registration');

            return null;
        }

        return $pending;
    }

    private function discard(PendingTechnicianRegistration $pending): void
    {
        Storage::disk('local')->delete(array_values($pending->payload['paths'] ?? []));
        $pending->delete();
    }

    /** @return array<string, string> */
    private function documentMessages(): array
    {
        return [
            'valid_id.uploaded' => 'The valid ID could not be uploaded. Use a JPG, PNG, or WEBP image no larger than 5 MB.',
            'valid_id.max' => 'The valid ID must not be larger than 5 MB.',
            'valid_id.mimes' => 'The valid ID must be a JPG, PNG, or WEBP image.',
            'valid_id.mimetypes' => 'The valid ID must be a JPG, PNG, or WEBP image.',
            'credentials.uploaded' => 'The credentials could not be uploaded. Use a PDF, DOC, or DOCX file no larger than 5 MB.',
            'credentials.max' => 'The credentials must not be larger than 5 MB.',
            'credentials.mimes' => 'The credentials must be a PDF, DOC, or DOCX file.',
            'credentials.mimetypes' => 'The credentials must be a PDF, DOC, or DOCX file.',
        ];
    }
}
