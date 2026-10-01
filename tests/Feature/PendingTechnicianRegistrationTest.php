<?php

use App\Livewire\SuperAdmin\ModulePage;
use App\Models\PendingTechnicianRegistration;
use App\Models\User;
use App\Notifications\TechnicianEmailVerificationCodeNotification;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Symfony\Component\HttpKernel\Exception\HttpException;

beforeEach(function () {
    Storage::fake('local');
    Storage::fake('public');
    Notification::fake();
    config(['mail.default' => 'smtp']);
});

/** @return array<string, mixed> */
function pendingTechnicianInput(array $overrides = []): array
{
    return [
        'first_name' => 'Alex',
        'surname' => 'Rivera',
        'email' => 'alex@example.com',
        'role' => 'technician',
        'phone' => '09171234567',
        'service_categories' => ['plumbing'],
        'service_area' => 'Quezon City',
        'service_type' => 'home',
        'years_experience' => 4,
        'password' => 'password',
        'password_confirmation' => 'password',
        'valid_id' => UploadedFile::fake()->create('id.jpg', 80, 'image/jpeg'),
        'credentials' => UploadedFile::fake()->create('credentials.pdf', 128, 'application/pdf'),
        'profile_photo' => UploadedFile::fake()->create('portrait.png', 80, 'image/png'),
        ...$overrides,
    ];
}

test('technician account and photo are saved only after email confirmation', function () {
    $this->post(route('technician.registration.store'), pendingTechnicianInput())
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('technician.registration.show'));

    $this->assertGuest();
    $this->assertDatabaseMissing('users', ['email' => 'alex@example.com']);
    expect(PendingTechnicianRegistration::query()->count())->toBe(1);

    $pending = PendingTechnicianRegistration::query()->firstOrFail();
    expect($pending->payload['password'])->not->toBe('password');
    Storage::disk('local')->assertExists($pending->payload['paths']['valid_id']);
    Storage::disk('public')->assertMissing('avatars');

    $code = null;
    Notification::assertSentOnDemand(
        TechnicianEmailVerificationCodeNotification::class,
        function (TechnicianEmailVerificationCodeNotification $notification) use (&$code): bool {
            $code = $notification->code;

            return true;
        },
    );

    $this->post(route('technician.registration.verify'), ['code' => '000000'])
        ->assertSessionHasErrors('code');
    $this->assertDatabaseMissing('users', ['email' => 'alex@example.com']);

    $this->post(route('technician.registration.verify'), ['code' => $code])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('technician.module'));

    $user = User::query()->where('email', 'alex@example.com')->firstOrFail();
    expect($user->email_verified_at)->not->toBeNull()
        ->and($user->account_status)->toBe(User::ACCOUNT_REVIEW_PENDING)
        ->and($user->avatar_path)->not->toBeNull()
        ->and($user->technicianVerification->documents)->toHaveCount(2)
        ->and(PendingTechnicianRegistration::query()->count())->toBe(0);
    Storage::disk('public')->assertExists($user->avatar_path);
    Storage::disk('local')->assertMissing($pending->payload['paths']['profile_photo']);
});

test('changing the pending email invalidates the old code', function () {
    $this->post(route('technician.registration.store'), pendingTechnicianInput())
        ->assertSessionHasNoErrors();

    $firstCode = null;
    Notification::assertSentOnDemand(
        TechnicianEmailVerificationCodeNotification::class,
        function (TechnicianEmailVerificationCodeNotification $notification) use (&$firstCode): bool {
            $firstCode = $notification->code;

            return true;
        },
    );

    $this->post(route('technician.registration.email.update'), ['email' => 'new-alex@example.com'])
        ->assertSessionHasNoErrors();

    $pending = PendingTechnicianRegistration::query()->firstOrFail();
    expect($pending->email)->toBe('new-alex@example.com');

    $this->post(route('technician.registration.verify'), ['code' => $firstCode])
        ->assertSessionHasErrors('code');
    $this->assertDatabaseMissing('users', ['email' => 'alex@example.com']);
    $this->assertDatabaseMissing('users', ['email' => 'new-alex@example.com']);

    $replacementCode = null;
    Notification::assertSentOnDemand(
        TechnicianEmailVerificationCodeNotification::class,
        function (TechnicianEmailVerificationCodeNotification $notification) use ($pending, &$replacementCode): bool {
            if (! Hash::check($notification->code, $pending->code_hash)) {
                return false;
            }

            $replacementCode = $notification->code;

            return true;
        },
    );

    $this->post(route('technician.registration.verify'), ['code' => $replacementCode])
        ->assertSessionHasNoErrors();

    $this->assertDatabaseHas('users', ['email' => 'new-alex@example.com']);
    $this->assertDatabaseMissing('users', ['email' => 'alex@example.com']);
});

test('an already registered email cannot replace the pending email', function () {
    User::factory()->create(['email' => 'taken@example.com']);

    $this->post(route('technician.registration.store'), pendingTechnicianInput())
        ->assertSessionHasNoErrors();

    $this->post(route('technician.registration.email.update'), ['email' => 'taken@example.com'])
        ->assertSessionHasErrors('email');

    expect(PendingTechnicianRegistration::query()->firstOrFail()->email)->toBe('alex@example.com');
});

test('failed email change keeps the original address and code', function () {
    $this->post(route('technician.registration.store'), pendingTechnicianInput())
        ->assertSessionHasNoErrors();

    $pending = PendingTechnicianRegistration::query()->firstOrFail();
    $originalCodeHash = $pending->code_hash;
    config(['mail.default' => 'log']);

    $this->post(route('technician.registration.email.update'), ['email' => 'unreachable@example.com'])
        ->assertSessionHasErrors('email');

    expect($pending->fresh()->email)->toBe('alex@example.com')
        ->and($pending->fresh()->code_hash)->toBe($originalCodeHash);
});

test('profile photos larger than 5 MB are rejected before creating accounts', function () {
    $this->from(route('register'))
        ->post(route('technician.registration.store'), pendingTechnicianInput([
            'profile_photo' => UploadedFile::fake()->create('large-photo.jpg', 5200, 'image/jpeg'),
        ]))
        ->assertSessionHasErrors('profile_photo');

    $this->assertDatabaseMissing('users', ['email' => 'alex@example.com']);
    expect(PendingTechnicianRegistration::query()->count())->toBe(0);
});

test('mail delivery failure leaves only a pending registration', function () {
    config(['mail.default' => 'log']);

    $this->post(route('technician.registration.store'), pendingTechnicianInput())
        ->assertRedirect(route('technician.registration.show'))
        ->assertSessionHas('email_delivery_error');

    $this->assertDatabaseMissing('users', ['email' => 'alex@example.com']);
    expect(PendingTechnicianRegistration::query()->count())->toBe(1);
});

test('technician documents enforce image ID and PDF or Word credentials', function () {
    $this->from(route('register'))
        ->post(route('technician.registration.store'), pendingTechnicianInput([
            'valid_id' => UploadedFile::fake()->create('id.pdf', 20, 'application/pdf'),
            'credentials' => UploadedFile::fake()->create('credentials.jpg', 20, 'image/jpeg'),
        ]))
        ->assertRedirect(route('register'))
        ->assertSessionHasErrors(['valid_id', 'credentials']);

    expect(PendingTechnicianRegistration::query()->count())->toBe(0);

    $this->from(route('register'))
        ->post(route('technician.registration.store'), pendingTechnicianInput([
            'valid_id' => UploadedFile::fake()->create('id.jpg', 5200, 'image/jpeg'),
        ]))
        ->assertSessionHasErrors('valid_id');
});

test('Word credentials are accepted', function (string $filename, string $mimeType) {
    $this->post(route('technician.registration.store'), pendingTechnicianInput([
        'credentials' => UploadedFile::fake()->create($filename, 80, $mimeType),
    ]))
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('technician.registration.show'));

    expect(PendingTechnicianRegistration::query()->count())->toBe(1);
})->with([
    ['credentials.doc', 'application/msword'],
    ['credentials.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
]);

test('administrators can download Word credentials', function () {
    $admin = User::factory()->create(['role' => 'superadmin']);
    $technician = User::factory()->create(['role' => 'technician']);
    $verification = $technician->technicianVerification()->create([
        'status' => 'submitted',
        'risk_level' => 'low',
        'service_categories' => ['plumbing'],
        'years_experience' => 3,
        'submitted_at' => now(),
    ]);
    $path = 'technician-documents/test/credentials.docx';
    Storage::disk('local')->put($path, 'Word file bytes');
    $document = $verification->documents()->create([
        'type' => 'credentials',
        'label' => 'credentials.docx',
        'file_path' => $path,
        'status' => 'pending',
    ]);

    $response = $this->actingAs($admin)->get(route('admin.technician-documents.show', $document));

    $response->assertOk()
        ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');
    expect($response->headers->get('content-disposition'))->toStartWith('attachment;');
});

test('expired pending registration is discarded without creating an account', function () {
    $this->post(route('technician.registration.store'), pendingTechnicianInput())
        ->assertSessionHasNoErrors();

    $pending = PendingTechnicianRegistration::query()->firstOrFail();
    $validIdPath = $pending->payload['paths']['valid_id'];
    $this->travel(2)->days();

    $this->get(route('technician.registration.show'))->assertRedirect(route('register'));

    $this->assertDatabaseMissing('users', ['email' => 'alex@example.com']);
    expect(PendingTechnicianRegistration::query()->count())->toBe(0);
    Storage::disk('local')->assertMissing($validIdPath);
});

test('admin technician tabs separate applications from approved accounts', function () {
    $admin = User::factory()->create(['role' => 'superadmin']);
    $waiting = User::factory()->create(['role' => 'technician', 'name' => 'Waiting Technician']);
    $approved = User::factory()->create(['role' => 'technician', 'name' => 'Approved Technician']);

    foreach ([[$waiting, 'submitted'], [$approved, 'approved']] as [$technician, $status]) {
        $technician->technicianVerification()->create([
            'status' => $status,
            'risk_level' => 'low',
            'service_categories' => ['plumbing'],
            'years_experience' => 3,
            'submitted_at' => now(),
        ]);
    }

    Livewire::actingAs($admin)
        ->test(ModulePage::class, ['module' => 'technician-verification'])
        ->assertSee('Needs verification')
        ->assertSee('Verified accounts')
        ->assertSee('Waiting Technician')
        ->assertDontSee('Approved Technician')
        ->call('setVerificationFilter', 'verified')
        ->assertSee('Approved Technician')
        ->assertDontSee('Waiting Technician');
});

test('an administrator cannot approve a legacy technician with unverified email', function () {
    $admin = User::factory()->create(['role' => 'superadmin']);
    $technician = User::factory()->unverified()->create([
        'role' => 'technician',
        'account_status' => User::ACCOUNT_EMAIL_PENDING,
    ]);
    $verification = $technician->technicianVerification()->create([
        'status' => 'submitted',
        'risk_level' => 'low',
        'service_categories' => ['plumbing'],
        'years_experience' => 3,
        'submitted_at' => now(),
    ]);

    $this->actingAs($admin);

    expect(fn () => (new ModulePage)->updateVerificationStatus($verification->id, 'approved'))
        ->toThrow(HttpException::class);

    expect($verification->fresh()->status)->toBe('submitted')
        ->and($technician->fresh()->account_status)->toBe(User::ACCOUNT_EMAIL_PENDING);
});
