<?php

use App\Models\PendingTechnicianRegistration;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');
    Storage::fake('public');
});

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function pendingGoogleRegistration(array $overrides = []): array
{
    return array_merge([
        'google_id' => 'google-new-user-123',
        'email' => 'new-google-user@example.com',
        'name' => 'Google New User',
        'first_name' => 'Google',
        'surname' => 'User',
        'picture' => 'https://example.com/avatar.jpg',
        'expires_at' => now()->addMinutes(15)->getTimestamp(),
    ], $overrides);
}

/** @return array{valid_id: UploadedFile, credentials: UploadedFile} */
function googleTechnicianDocuments(): array
{
    return [
        'valid_id' => UploadedFile::fake()->create('valid-id.jpg', 80, 'image/jpeg'),
        'credentials' => UploadedFile::fake()->create('credentials.pdf', 120, 'application/pdf'),
    ];
}

test('a new verified google identity is sent to account type selection', function () {
    config()->set('services.google', [
        'client_id' => 'client-id',
        'client_secret' => 'client-secret',
        'redirect' => route('auth.google.callback'),
    ]);

    Http::fake([
        'https://oauth2.googleapis.com/token' => Http::response(['access_token' => 'google-access-token']),
        'https://openidconnect.googleapis.com/v1/userinfo' => Http::response([
            'sub' => 'new-google-sub',
            'email' => 'new-google@example.com',
            'email_verified' => true,
            'name' => 'Maria Santos',
            'given_name' => 'Maria',
            'family_name' => 'Santos',
            'picture' => 'https://example.com/maria.jpg',
        ]),
    ]);

    $response = $this->withSession(['google_oauth_state' => 'new-user-state'])
        ->get(route('auth.google.callback', [
            'code' => 'authorization-code',
            'state' => 'new-user-state',
        ]));

    $response->assertRedirect(route('auth.google.register.show'))
        ->assertSessionHas('google_registration', fn (array $profile): bool => $profile['google_id'] === 'new-google-sub'
            && $profile['email'] === 'new-google@example.com'
            && $profile['first_name'] === 'Maria'
            && $profile['surname'] === 'Santos');
    $this->assertGuest();
    $this->assertDatabaseMissing('users', ['email' => 'new-google@example.com']);
});

test('google onboarding shows verified profile data without password fields', function () {
    $this->withSession(['google_registration' => pendingGoogleRegistration()])
        ->get(route('auth.google.register.show'))
        ->assertOk()
        ->assertSee('Google verified new-google-user@example.com')
        ->assertSee('data-test="google-registration-notice"', false)
        ->assertSee('value="Google"', false)
        ->assertSee('value="User"', false)
        ->assertSee('value="new-google-user@example.com"', false)
        ->assertSee('readonly', false)
        ->assertSee(route('auth.google.register.store'))
        ->assertDontSee('name="password"', false)
        ->assertDontSee('name="password_confirmation"', false);
});

test('a new customer can finish registration with google', function () {
    $response = $this->withSession(['google_registration' => pendingGoogleRegistration()])
        ->post(route('auth.google.register.store'), [
            'role' => 'customer',
            'first_name' => 'Google',
            'middle_name' => 'Marie',
            'surname' => 'User',
            'phone' => '09171234567',
            'address' => '12 Sample Street, Quezon City',
            'email' => 'tampered@example.com',
            'profile_photo' => UploadedFile::fake()->create('customer.jpg', 80, 'image/jpeg'),
        ]);

    $response->assertSessionHasNoErrors()
        ->assertRedirect(route('customer.module'))
        ->assertSessionMissing('google_registration');

    $customer = User::query()->where('email', 'new-google-user@example.com')->firstOrFail();

    $this->assertAuthenticatedAs($customer);
    expect($customer->role)->toBe('customer')
        ->and($customer->name)->toBe('Google Marie User')
        ->and($customer->google_id)->toBe('google-new-user-123')
        ->and($customer->email_verified_at)->not->toBeNull()
        ->and($customer->password)->toBeNull()
        ->and($customer->account_status)->toBe('active')
        ->and($customer->phone)->toBe('09171234567')
        ->and($customer->address)->toBe('12 Sample Street, Quezon City')
        ->and($customer->avatar_path)->not->toBeNull();
    Storage::disk('public')->assertExists($customer->avatar_path);
});

test('a new technician can submit a google verified application without email otp', function () {
    $response = $this->withSession([
        'google_registration' => pendingGoogleRegistration([
            'google_id' => 'google-technician-123',
            'email' => 'google-technician@example.com',
        ]),
    ])->post(route('auth.google.register.store'), [
        'role' => 'technician',
        'first_name' => 'Google',
        'surname' => 'Technician',
        'phone' => '09179876543',
        'service_categories' => ['plumbing', 'electrical'],
        'service_area' => 'Quezon City',
        'service_type' => 'both',
        'shop_name' => 'Google Repair Hub',
        'years_experience' => 6,
        ...googleTechnicianDocuments(),
    ]);

    $response->assertSessionHasNoErrors()
        ->assertRedirect(route('technician.module'))
        ->assertSessionHas('status', 'Your Google email is verified. Your technician application is waiting for staff review.')
        ->assertSessionMissing('google_registration');

    $technician = User::query()->where('email', 'google-technician@example.com')->firstOrFail();

    $this->assertAuthenticatedAs($technician);
    expect($technician->role)->toBe('technician')
        ->and($technician->google_id)->toBe('google-technician-123')
        ->and($technician->email_verified_at)->not->toBeNull()
        ->and($technician->password)->toBeNull()
        ->and($technician->account_status)->toBe(User::ACCOUNT_REVIEW_PENDING)
        ->and($technician->technicianVerification->status)->toBe('submitted')
        ->and($technician->technicianVerification->service_categories)->toBe(['plumbing', 'electrical'])
        ->and($technician->technicianVerification->shop_name)->toBe('Google Repair Hub')
        ->and($technician->technicianVerification->documents)->toHaveCount(2)
        ->and(PendingTechnicianRegistration::query()->count())->toBe(0);

    foreach ($technician->technicianVerification->documents as $document) {
        Storage::disk('local')->assertExists($document->file_path);
    }
});

test('google onboarding reports an account created before submission', function () {
    User::factory()->create(['email' => 'new-google-user@example.com']);

    $this->withSession(['google_registration' => pendingGoogleRegistration()])
        ->from(route('auth.google.register.show'))
        ->post(route('auth.google.register.store'), [
            'role' => 'customer',
            'first_name' => 'Google',
            'surname' => 'User',
            'phone' => '09171234567',
            'address' => 'Quezon City',
        ])
        ->assertRedirect(route('auth.google.register.show'))
        ->assertSessionHasErrors([
            'email' => 'This email is already registered in FixTrack. Return to sign in and continue with Google.',
        ]);

    $this->assertGuest();
    expect(User::query()->where('email', 'new-google-user@example.com')->count())->toBe(1);
});

test('expired google onboarding sessions return to sign in with a message', function () {
    $this->withSession([
        'google_registration' => pendingGoogleRegistration([
            'expires_at' => now()->subMinute()->getTimestamp(),
        ]),
    ])->get(route('auth.google.register.show'))
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors([
            'email' => 'Your Google registration session expired. Please continue with Google again.',
        ])
        ->assertSessionMissing('google_registration');
});

test('google only accounts cannot sign in using an arbitrary password', function () {
    $user = User::factory()->create([
        'email' => 'google-only@example.com',
        'google_id' => 'google-only-sub',
        'password' => null,
    ]);

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'any-password',
    ])->assertSessionHasErrors('email');

    $this->assertGuest();
});
