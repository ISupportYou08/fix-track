<?php

use App\Models\PendingTechnicianRegistration;
use App\Models\ServiceCatalog;
use App\Models\User;
use App\Notifications\TechnicianEmailVerificationCodeNotification;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Laravel\Fortify\Features;

beforeEach(function () {
    $this->skipUnlessFortifyHas(Features::registration());
    Storage::fake('local');
    Notification::fake();
    config(['mail.default' => 'smtp']);
});

/** @return array{valid_id: UploadedFile, credentials: UploadedFile} */
function technicianRegistrationDocuments(): array
{
    return [
        'valid_id' => UploadedFile::fake()->create('valid-id.jpg', 80, 'image/jpeg'),
        'credentials' => UploadedFile::fake()->create('credentials.pdf', 120, 'application/pdf'),
    ];
}

test('registration screen can be rendered', function () {
    $response = $this->get(route('register'));

    $response->assertOk()
        ->assertSee('data-test="registration-role-selector"', false)
        ->assertSee('x-cloak', false)
        ->assertSee('registration-geometric-lines', false)
        ->assertSee('registration-glass', false)
        ->assertSee('registration-role-card', false)
        ->assertSee('Create your account')
        ->assertSee('Choose how you will use FixTrack.')
        ->assertSee('Customer')
        ->assertSee('Technician')
        ->assertSee('data-customer-registration-form', escape: false)
        ->assertSee('customer-registration-abstract.jpg')
        ->assertSee('blur-[3px]')
        ->assertSee('bg-white/85')
        ->assertSee('name="first_name"', escape: false)
        ->assertSee('name="surname"', escape: false)
        ->assertSee('name="phone"', escape: false)
        ->assertSee('name="address"', escape: false)
        ->assertSee("x-data=\"technicianRegistration(null, 'both')\"", escape: false)
        ->assertSee('data-technician-password-column', escape: false)
        ->assertSee('data-technician-password-confirmation-column', escape: false)
        ->assertSee('data-technician-registration-back', escape: false)
        ->assertSee('aria-label="Back to account type selection"', escape: false)
        ->assertSee('maximum 5 MB for each file')
        ->assertDontSee('chooseRole(value)')
        ->assertSee('fixtrack-logo.png');
});

test('new users can register', function () {
    $response = $this->post(route('register.store'), [
        'first_name' => 'John',
        'middle_name' => 'Rafael',
        'surname' => 'Doe',
        'phone' => '09171234567',
        'address' => '12 Sample Street, Quezon City',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'role' => 'customer',
    ]);

    $response->assertSessionHasNoErrors()
        ->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticated();

    expect(auth()->user()->role)->toBe('customer')
        ->and(auth()->user()->name)->toBe('John Rafael Doe')
        ->and(auth()->user()->first_name)->toBe('John')
        ->and(auth()->user()->middle_name)->toBe('Rafael')
        ->and(auth()->user()->surname)->toBe('Doe')
        ->and(auth()->user()->phone)->toBe('09171234567')
        ->and(auth()->user()->address)->toBe('12 Sample Street, Quezon City');
});

test('customer registration stores an optional profile photo', function () {
    Storage::fake('public');

    $this->post(route('register.store'), [
        'first_name' => 'Photo',
        'surname' => 'Customer',
        'phone' => '09171234568',
        'address' => '34 Portrait Avenue, Manila',
        'email' => 'photo-customer@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'role' => 'customer',
        'profile_photo' => UploadedFile::fake()->create('portrait.jpg', 80, 'image/jpeg'),
    ])->assertSessionHasNoErrors();

    $customer = User::query()->where('email', 'photo-customer@example.com')->firstOrFail();
    expect($customer->avatar_path)->not->toBeNull();
    Storage::disk('public')->assertExists($customer->avatar_path);
});

test('customer registration requires the identity and contact fields shown on the form', function () {
    $this->from(route('register'))
        ->post(route('register.store'), [
            'email' => 'missing-details@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role' => 'customer',
        ])
        ->assertRedirect(route('register'))
        ->assertSessionHasErrors(['first_name', 'surname', 'phone', 'address']);

    $this->assertDatabaseMissing('users', ['email' => 'missing-details@example.com']);
});

test('technician cannot bypass pending email verification through customer registration', function () {
    $this->from(route('register'))
        ->post(route('register.store'), [
            'name' => 'Bypass Technician',
            'email' => 'bypass@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role' => 'technician',
        ])
        ->assertRedirect(route('register'))
        ->assertSessionHasErrors('role');

    $this->assertDatabaseMissing('users', ['email' => 'bypass@example.com']);
});

test('technician registration holds application data until email verification', function () {
    Notification::fake();
    config(['mail.default' => 'smtp']);

    $response = $this->post(route('technician.registration.store'), [
        'first_name' => 'Alex Jr.',
        'middle_name' => 'Service',
        'surname' => 'Technician',
        'email' => 'technician@example.com',
        'role' => 'technician',
        ...technicianRegistrationDocuments(),
        'phone' => '09171234567',
        'service_category' => 'plumbing',
        'service_area' => 'Quezon City',
        'service_type' => 'both',
        'shop_name' => 'Alex Home and Shop Repair',
        'years_experience' => 5,
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertSessionHasNoErrors()
        ->assertRedirect(route('technician.registration.show'));

    $this->assertGuest();
    $this->assertDatabaseMissing('users', ['email' => 'technician@example.com']);
    $pending = PendingTechnicianRegistration::query()->firstOrFail();

    expect($pending->payload['name'])->toBe('Alex Jr. Service Technician')
        ->and($pending->payload['first_name'])->toBe('Alex Jr.')
        ->and($pending->payload['middle_name'])->toBe('Service')
        ->and($pending->payload['surname'])->toBe('Technician')
        ->and($pending->payload['phone'])->toBe('09171234567')
        ->and($pending->payload['service_categories'])->toBe(['plumbing'])
        ->and($pending->payload['service_type'])->toBe('both')
        ->and($pending->payload['shop_name'])->toBe('Alex Home and Shop Repair');

    Storage::disk('local')->assertExists($pending->payload['paths']['valid_id']);
    Storage::disk('local')->assertExists($pending->payload['paths']['credentials']);
    Notification::assertSentOnDemand(TechnicianEmailVerificationCodeNotification::class);
});

test('technician registration continues through OTP confirmation to staff review', function () {
    Notification::fake();
    config(['mail.default' => 'smtp']);

    $this->post(route('technician.registration.store'), [
        'first_name' => 'Flow',
        'surname' => 'Technician',
        'email' => 'flow-technician@example.com',
        'role' => 'technician',
        ...technicianRegistrationDocuments(),
        'phone' => '09171234567',
        'service_categories' => ['plumbing'],
        'service_area' => 'Quezon City',
        'service_type' => 'home',
        'years_experience' => 3,
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertSessionHasNoErrors()
        ->assertRedirect(route('technician.registration.show'));

    $this->assertDatabaseMissing('users', ['email' => 'flow-technician@example.com']);
    $verificationCode = null;

    Notification::assertSentOnDemand(
        TechnicianEmailVerificationCodeNotification::class,
        function (TechnicianEmailVerificationCodeNotification $notification) use (&$verificationCode): bool {
            $verificationCode = $notification->code;

            return true;
        },
    );

    $this->get(route('technician.registration.show'))
        ->assertOk()
        ->assertSee('Confirm your email');

    $this->post(route('technician.registration.verify'), ['code' => $verificationCode])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('technician.module'));

    $technician = User::query()->where('email', 'flow-technician@example.com')->firstOrFail();

    expect($technician->email_verified_at)->not->toBeNull()
        ->and($technician->account_status)->toBe(User::ACCOUNT_REVIEW_PENDING)
        ->and($technician->technicianVerification->documents)->toHaveCount(2);

    $this->get(route('technician.module'))
        ->assertOk()
        ->assertSee('Application under review');
});

test('technician registration explains document size failures', function () {
    $documents = technicianRegistrationDocuments();
    $documents['credentials'] = UploadedFile::fake()->create('large-credentials.pdf', 5200, 'application/pdf');

    $this->from(route('register'))
        ->post(route('technician.registration.store'), [
            'first_name' => 'Large File',
            'surname' => 'Technician',
            'email' => 'large-file-technician@example.com',
            'role' => 'technician',
            ...$documents,
            'phone' => '09171234567',
            'service_categories' => ['plumbing'],
            'service_area' => 'Quezon City',
            'service_type' => 'home',
            'years_experience' => 3,
            'password' => 'password',
            'password_confirmation' => 'password',
        ])
        ->assertRedirect(route('register'))
        ->assertSessionHasErrors([
            'credentials' => 'The credentials must not be larger than 5 MB.',
        ]);

    expect(User::query()->where('email', 'large-file-technician@example.com')->exists())->toBeFalse();
});

test('technician registration rejects a password confirmation mismatch', function () {
    $this->from(route('register'))
        ->post(route('technician.registration.store'), [
            'first_name' => 'Password',
            'surname' => 'Mismatch',
            'email' => 'password-mismatch-technician@example.com',
            'role' => 'technician',
            ...technicianRegistrationDocuments(),
            'phone' => '09171234567',
            'service_categories' => ['plumbing'],
            'service_area' => 'Quezon City',
            'service_type' => 'home',
            'years_experience' => 3,
            'password' => 'password',
            'password_confirmation' => 'different-password',
        ])
        ->assertRedirect(route('register'))
        ->assertSessionHasErrors('password');

    expect(User::query()->where('email', 'password-mismatch-technician@example.com')->exists())->toBeFalse();
});

test('technician registration follows active service catalog categories', function () {
    ServiceCatalog::create([
        'code' => 'solar',
        'name' => 'Solar panel repair',
        'category' => 'Renewable energy',
        'is_active' => true,
    ]);

    $this->get(route('register'))
        ->assertOk()
        ->assertSee('Electrical repair')
        ->assertSee('Solar panel repair');

    $response = $this->post(route('technician.registration.store'), [
        'first_name' => 'Solar',
        'surname' => 'Technician',
        'email' => 'solar-technician@example.com',
        'role' => 'technician',
        ...technicianRegistrationDocuments(),
        'phone' => '09171234567',
        'service_category' => 'solar',
        'service_area' => 'Quezon City',
        'service_type' => 'home',
        'years_experience' => 5,
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertSessionHasNoErrors()
        ->assertRedirect(route('technician.registration.show'));

    $this->assertGuest();
    expect(PendingTechnicianRegistration::query()->firstOrFail()->payload['service_categories'])->toBe(['solar'])
        ->and(PendingTechnicianRegistration::query()->firstOrFail()->payload['shop_name'])->toBeNull();
});

test('technician registration stores multiple exact service capabilities', function () {
    $response = $this->post(route('technician.registration.store'), [
        'first_name' => 'Electronics',
        'surname' => 'Technician',
        'email' => 'electronics-technician@example.com',
        'role' => 'technician',
        ...technicianRegistrationDocuments(),
        'phone' => '09171234567',
        'service_categories' => ['electronics-lcd', 'electronics-battery'],
        'service_area' => 'Quezon City',
        'service_type' => 'walkin',
        'shop_name' => 'Electronics Service Hub',
        'years_experience' => 5,
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertSessionHasNoErrors()
        ->assertRedirect(route('technician.registration.show'));

    expect(PendingTechnicianRegistration::query()->firstOrFail()->payload['service_categories'])
        ->toBe(['electronics-lcd', 'electronics-battery'])
        ->and(PendingTechnicianRegistration::query()->firstOrFail()->payload['shop_name'])->toBe('Electronics Service Hub');
});

test('walk-in and both technician registrations require a shop name', function (string $serviceType) {
    $response = $this->from(route('register'))
        ->post(route('technician.registration.store'), [
            'first_name' => 'No Shop',
            'surname' => 'Technician',
            'email' => "no-shop-{$serviceType}@example.com",
            'role' => 'technician',
            ...technicianRegistrationDocuments(),
            'phone' => '09171234567',
            'service_categories' => ['plumbing'],
            'service_area' => 'Quezon City',
            'service_type' => $serviceType,
            'years_experience' => 5,
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

    $response->assertRedirect(route('register'))
        ->assertSessionHasErrors('shop_name');

    $this->assertGuest();
    expect(User::query()->where('email', "no-shop-{$serviceType}@example.com")->exists())->toBeFalse();
})->with(['walkin', 'both']);

test('technician registration requires at least one exact service capability', function () {
    $response = $this->from(route('register'))
        ->post(route('technician.registration.store'), [
            'first_name' => 'Unskilled',
            'surname' => 'Technician',
            'email' => 'unskilled-technician@example.com',
            'role' => 'technician',
            ...technicianRegistrationDocuments(),
            'phone' => '09171234567',
            'service_categories' => [],
            'service_area' => 'Quezon City',
            'service_type' => 'both',
            'shop_name' => 'Unskilled Repair Shop',
            'years_experience' => 5,
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

    $response->assertRedirect(route('register'))
        ->assertSessionHasErrors('service_categories');

    $this->assertGuest();
});

test('technician registration rolls back the user when verification creation fails', function () {
    $this->post(route('technician.registration.store'), [
        'first_name' => 'Rollback',
        'surname' => 'Technician',
        'email' => 'rollback-technician@example.com',
        'role' => 'technician',
        ...technicianRegistrationDocuments(),
        'phone' => '09171234567',
        'service_category' => 'plumbing',
        'service_area' => 'Quezon City',
        'service_type' => 'both',
        'shop_name' => 'Rollback Repair Shop',
        'years_experience' => 5,
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertSessionHasNoErrors();

    $verificationCode = null;
    Notification::assertSentOnDemand(
        TechnicianEmailVerificationCodeNotification::class,
        function (TechnicianEmailVerificationCodeNotification $notification) use (&$verificationCode): bool {
            $verificationCode = $notification->code;

            return true;
        },
    );

    Event::listen('eloquent.creating: App\\Models\\TechnicianVerification', function (): void {
        throw new RuntimeException('Verification storage unavailable.');
    });

    try {
        $this->withoutExceptionHandling();
        expect(fn () => $this->post(route('technician.registration.verify'), ['code' => $verificationCode]))
            ->toThrow(RuntimeException::class, 'Verification storage unavailable.');
    } finally {
        Event::forget('eloquent.creating: App\\Models\\TechnicianVerification');
    }

    expect(User::query()->where('email', 'rollback-technician@example.com')->exists())->toBeFalse();
    expect(PendingTechnicianRegistration::query()->where('email', 'rollback-technician@example.com')->exists())->toBeTrue();
});

test('registration cannot self assign an operations role', function (string $role) {
    $email = $role.'-signup@example.com';

    $response = $this->from(route('register'))
        ->post(route('register.store'), [
            'name' => 'Attempted Operations User',
            'email' => $email,
            'role' => $role,
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

    $response->assertRedirect(route('register'))
        ->assertSessionHasErrors('role');

    $this->assertGuest();
    $this->assertDatabaseMissing('users', ['email' => $email]);
})->with(['staff', 'superadmin']);
