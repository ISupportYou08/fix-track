<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\CreatesNewUsers;
use Throwable;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules, ProfileValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, mixed>  $input
     */
    public function create(array $input): User
    {
        if (($input['role'] ?? null) === 'technician') {
            throw ValidationException::withMessages([
                'role' => 'Use the technician application form to create a technician account.',
            ]);
        }

        $validated = Validator::make($input, [
            'first_name' => ['required', 'string', 'max:80'],
            'middle_name' => ['nullable', 'string', 'max:80'],
            'surname' => ['required', 'string', 'max:80'],
            'phone' => ['required', 'string', 'max:30'],
            'address' => ['required', 'string', 'max:255'],
            'email' => $this->emailRules(),
            'password' => $this->passwordRules(),
            'role' => ['required', Rule::in(['customer'])],
            'profile_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ])->validate();

        $avatarPath = null;

        try {
            if (isset($validated['profile_photo'])) {
                $avatarPath = $validated['profile_photo']->store('avatars', 'public');
            }

            return User::create([
                'name' => implode(' ', array_filter([
                    $validated['first_name'],
                    $validated['middle_name'] ?? null,
                    $validated['surname'],
                ])),
                'first_name' => $validated['first_name'],
                'middle_name' => $validated['middle_name'] ?? null,
                'surname' => $validated['surname'],
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'address' => $validated['address'],
                'password' => $validated['password'],
                'role' => 'customer',
                'account_status' => 'active',
                'avatar_path' => $avatarPath,
            ]);
        } catch (Throwable $exception) {
            if (is_string($avatarPath)) {
                Storage::disk('public')->delete($avatarPath);
            }

            throw $exception;
        }
    }
}
