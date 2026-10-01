<?php

namespace App\Actions\Auth;

use App\Models\ServiceCatalog;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

class CompleteGoogleRegistration
{
    /**
     * @param  array<string, mixed>  $data
     * @param  array{google_id: string, email: string, name: string, first_name: string, surname: string, picture: string|null, expires_at: int}  $googleProfile
     */
    public function execute(array $data, array $googleProfile): User
    {
        $avatarPath = null;
        $documentPaths = [];

        try {
            if (($data['profile_photo'] ?? null) instanceof UploadedFile) {
                $avatarPath = $data['profile_photo']->store('avatars', 'public');

                if (! is_string($avatarPath)) {
                    throw new RuntimeException('Unable to store the profile photo.');
                }
            }

            if ($data['role'] === 'technician') {
                $directory = 'technician-documents/'.Str::uuid();

                foreach (['valid_id', 'credentials'] as $field) {
                    $path = $data[$field]->store($directory, 'local');

                    if (! is_string($path)) {
                        throw new RuntimeException("Unable to store {$field}.");
                    }

                    $documentPaths[$field] = $path;
                }
            }

            return DB::transaction(function () use ($data, $googleProfile, $avatarPath, $documentPaths): User {
                $existingUser = User::query()
                    ->where(function ($query) use ($googleProfile): void {
                        $query->where('email', $googleProfile['email'])
                            ->orWhere('google_id', $googleProfile['google_id']);
                    })
                    ->lockForUpdate()
                    ->first();

                if ($existingUser instanceof User) {
                    throw ValidationException::withMessages([
                        'email' => $this->existingAccountMessage($existingUser),
                    ]);
                }

                $middleName = filled($data['middle_name'] ?? null) ? $data['middle_name'] : null;
                $name = Str::of(implode(' ', array_filter([
                    $data['first_name'],
                    $middleName,
                    $data['surname'],
                ])))->squish()->toString();

                $user = User::create([
                    'name' => $name,
                    'first_name' => $data['first_name'],
                    'middle_name' => $middleName,
                    'surname' => $data['surname'],
                    'email' => $googleProfile['email'],
                    'google_id' => $googleProfile['google_id'],
                    'password' => null,
                    'role' => $data['role'],
                    'phone' => $data['phone'],
                    'address' => $data['role'] === 'customer' ? $data['address'] : null,
                    'avatar_path' => $avatarPath,
                    'account_status' => $data['role'] === 'technician'
                        ? User::ACCOUNT_REVIEW_PENDING
                        : 'active',
                ]);
                $user->forceFill(['email_verified_at' => now()])->save();

                if ($user->isTechnician()) {
                    $verification = $user->technicianVerification()->create([
                        'status' => 'submitted',
                        'risk_level' => 'low',
                        'service_categories' => ServiceCatalog::normalizeCodes($data['service_categories']),
                        'years_experience' => (int) $data['years_experience'],
                        'service_area' => $data['service_area'],
                        'service_type' => $data['service_type'],
                        'shop_name' => $data['shop_name'] ?? null,
                        'phone' => $data['phone'],
                        'address' => $data['service_area'],
                        'submitted_at' => now(),
                    ]);

                    foreach (['valid_id', 'credentials'] as $field) {
                        $verification->documents()->create([
                            'type' => $field,
                            'label' => $data[$field]->getClientOriginalName(),
                            'file_path' => $documentPaths[$field],
                            'status' => 'pending',
                        ]);
                    }
                }

                return $user;
            });
        } catch (Throwable $exception) {
            if (is_string($avatarPath)) {
                Storage::disk('public')->delete($avatarPath);
            }

            Storage::disk('local')->delete(array_values($documentPaths));

            throw $exception;
        }
    }

    private function existingAccountMessage(User $user): string
    {
        return match ($user->account_status) {
            User::ACCOUNT_BANNED => 'This Google email belongs to a banned FixTrack account. Contact support for assistance.',
            'suspended' => 'This Google email belongs to a suspended FixTrack account. Contact support for assistance.',
            default => 'This email is already registered in FixTrack. Return to sign in and continue with Google.',
        };
    }
}
