<?php

use App\Livewire\SuperAdmin\ModulePage;
use App\Models\EmailVerificationCode;
use App\Models\TechnicianVerification;
use App\Models\User;
use App\Notifications\TechnicianApplicationDecisionNotification;
use App\Notifications\TechnicianEmailVerificationCodeNotification;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

function onboardingTechnician(string $accountStatus, ?string $verificationStatus = 'submitted'): User
{
    $technician = User::factory()->create([
        'role' => 'technician',
        'account_status' => $accountStatus,
        'email_verified_at' => $accountStatus === User::ACCOUNT_EMAIL_PENDING ? null : now(),
    ]);

    TechnicianVerification::create([
        'user_id' => $technician->id,
        'status' => $verificationStatus,
        'risk_level' => 'low',
        'service_categories' => ['plumbing'],
        'years_experience' => 4,
        'service_area' => 'Quezon City',
        'service_type' => 'both',
        'shop_name' => 'Reliable Repair Shop',
        'phone' => '09171234567',
        'submitted_at' => now(),
    ]);

    return $technician;
}

test('email pending technicians are redirected to OTP verification', function () {
    $technician = onboardingTechnician(User::ACCOUNT_EMAIL_PENDING);

    $this->actingAs($technician)
        ->get(route('technician.module'))
        ->assertRedirect(route('technician.email-otp.show'));

    $this->actingAs($technician)
        ->get(route('technician.email-otp.show'))
        ->assertOk()
        ->assertSee('Confirm your email')
        ->assertSee($technician->email);
});

test('local log mail does not expose an OTP or claim inbox delivery', function () {
    Notification::fake();
    config(['mail.default' => 'log']);
    $technician = onboardingTechnician(User::ACCOUNT_EMAIL_PENDING);

    $this->actingAs($technician)
        ->post(route('technician.email-otp.resend'))
        ->assertRedirect()
        ->assertSessionHasErrors('email')
        ->assertSessionMissing('local_technician_otp');

    expect($technician->emailVerificationCode()->exists())->toBeFalse();
    Notification::assertNotSentTo($technician, TechnicianEmailVerificationCodeNotification::class);

    $this->actingAs($technician)
        ->withSession(['local_technician_otp' => '123456'])
        ->get(route('technician.email-otp.show'))
        ->assertOk()
        ->assertDontSee('Local development code')
        ->assertDontSee('123456')
        ->assertSee('Sign out and use another account');
});

test('the landing page provides authentication entry points and a visible sign out path', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee('data-test="home-login"', false)
        ->assertSee('data-test="home-register"', false);

    $technician = onboardingTechnician(User::ACCOUNT_REVIEW_PENDING);

    $this->actingAs($technician)
        ->get(route('home'))
        ->assertOk()
        ->assertSee('Dashboard')
        ->assertSee('data-test="home-logout"', false);
});

test('a valid OTP verifies the email and sends the application to review', function () {
    $technician = onboardingTechnician(User::ACCOUNT_EMAIL_PENDING);
    EmailVerificationCode::create([
        'user_id' => $technician->id,
        'code_hash' => Hash::make('123456'),
        'expires_at' => now()->addMinutes(10),
    ]);

    $this->actingAs($technician)
        ->post(route('technician.email-otp.verify'), ['code' => '123456'])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('technician.module'));

    $technician->refresh();
    expect($technician->email_verified_at)->not->toBeNull()
        ->and($technician->account_status)->toBe(User::ACCOUNT_REVIEW_PENDING)
        ->and($technician->emailVerificationCode()->exists())->toBeFalse();

    $this->actingAs($technician)
        ->get(route('technician.module'))
        ->assertOk()
        ->assertSee('Application under review')
        ->assertSee('blur-[5px]', false);
});

test('an incorrect OTP is rejected and counted', function () {
    $technician = onboardingTechnician(User::ACCOUNT_EMAIL_PENDING);
    $code = EmailVerificationCode::create([
        'user_id' => $technician->id,
        'code_hash' => Hash::make('123456'),
        'expires_at' => now()->addMinutes(10),
    ]);

    $this->actingAs($technician)
        ->post(route('technician.email-otp.verify'), ['code' => '654321'])
        ->assertSessionHasErrors('code');

    expect($code->fresh()->attempts)->toBe(1)
        ->and($technician->fresh()->email_verified_at)->toBeNull();
});

test('admin approval activates the technician and sends a decision email', function () {
    Notification::fake();
    $admin = User::factory()->create(['role' => 'superadmin']);
    $technician = onboardingTechnician(User::ACCOUNT_REVIEW_PENDING);
    $document = $technician->technicianVerification->documents()->create([
        'type' => 'valid_id',
        'label' => 'proof.pdf',
        'file_path' => 'technician-documents/proof.pdf',
        'status' => 'pending',
    ]);

    Livewire::actingAs($admin)
        ->test(ModulePage::class, ['module' => 'technician-verification'])
        ->call('requestConfirmation', 'approve-verification', $technician->technicianVerification->id)
        ->call('executeConfirmedAction');

    expect($technician->fresh()->account_status)->toBe('active')
        ->and($technician->technicianVerification->fresh()->status)->toBe('approved')
        ->and($document->fresh()->status)->toBe('verified');
    Notification::assertSentTo($technician, TechnicianApplicationDecisionNotification::class);
});

test('verification dashboard searches stored applicants and opens their details', function () {
    Storage::fake('local');
    $admin = User::factory()->create(['role' => 'superadmin']);
    $matchingTechnician = onboardingTechnician(User::ACCOUNT_REVIEW_PENDING);
    $matchingTechnician->update(['name' => 'Alex Rivera']);
    $otherTechnician = onboardingTechnician(User::ACCOUNT_REVIEW_PENDING);
    $otherTechnician->update(['name' => 'Bea Santos']);
    $verification = $matchingTechnician->technicianVerification;
    Storage::disk('local')->put('technician-documents/identity.pdf', "%PDF-1.4\n%%EOF");
    $verification->documents()->create([
        'type' => 'valid_id',
        'label' => 'identity.pdf',
        'file_path' => 'technician-documents/identity.pdf',
        'status' => 'pending',
    ]);

    Livewire::actingAs($admin)
        ->test(ModulePage::class, ['module' => 'technician-verification'])
        ->assertSee('Alex Rivera')
        ->assertSee('Bea Santos')
        ->set('verificationSearch', 'Alex')
        ->assertSee('Alex Rivera')
        ->assertDontSee('Bea Santos')
        ->call('openVerificationDetails', $verification->id)
        ->assertSet('showVerificationDetails', true)
        ->assertSee('Technician Details')
        ->assertSee('View identity.pdf')
        ->assertSee(route('admin.technician-documents.show', $verification->documents()->firstOrFail()));
});

test('admin resubmission request saves its reason and updates technician access', function () {
    Notification::fake();
    $admin = User::factory()->create(['role' => 'superadmin']);
    $technician = onboardingTechnician(User::ACCOUNT_REVIEW_PENDING);
    $verification = $technician->technicianVerification;
    $reason = 'Please upload a clearer government ID.';

    Livewire::actingAs($admin)
        ->test(ModulePage::class, ['module' => 'technician-verification'])
        ->call('openVerificationDetails', $verification->id)
        ->call('requestConfirmation', 'request-verification-information', $verification->id)
        ->assertSet('showVerificationDetails', false)
        ->set('confirmationReason', $reason)
        ->call('executeConfirmedAction');

    $verification->refresh();

    expect($verification->status)->toBe('information_requested')
        ->and($verification->decision_reason)->toBe($reason)
        ->and($verification->reviewer_id)->toBe($admin->id)
        ->and($verification->reviewed_at)->not->toBeNull()
        ->and($technician->fresh()->account_status)->toBe(User::ACCOUNT_REJECTED);
    Notification::assertSentTo($technician, TechnicianApplicationDecisionNotification::class);
});

test('only administrators can preview stored technician documents', function () {
    Storage::fake('local');
    $admin = User::factory()->create(['role' => 'superadmin']);
    $technician = onboardingTechnician(User::ACCOUNT_REVIEW_PENDING);
    $path = 'technician-documents/'.$technician->id.'/identity.pdf';
    Storage::disk('local')->put($path, "%PDF-1.4\n%%EOF");
    $document = $technician->technicianVerification->documents()->create([
        'type' => 'valid_id',
        'label' => 'identity.pdf',
        'file_path' => $path,
        'status' => 'pending',
    ]);
    $documentUrl = route('admin.technician-documents.show', $document);

    $this->actingAs($admin)
        ->get($documentUrl)
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');

    $this->actingAs($technician)
        ->get($documentUrl)
        ->assertForbidden();

    Storage::disk('local')->delete($path);

    $this->actingAs($admin)
        ->get($documentUrl)
        ->assertNotFound();
});

test('a rejected technician sees the specific issue and can resubmit corrections', function () {
    Notification::fake();
    Storage::fake('local');
    $admin = User::factory()->create(['role' => 'superadmin']);
    $technician = onboardingTechnician(User::ACCOUNT_REVIEW_PENDING);
    $reason = 'The submitted shop name cannot be matched to the listed service address.';

    $verification = $technician->technicianVerification;
    Storage::disk('local')->put('technician-documents/old-valid-id.jpg', 'old valid ID');
    Storage::disk('local')->put('technician-documents/old-credentials.pdf', 'old credentials');
    $verification->documents()->createMany([
        [
            'type' => 'valid_id',
            'label' => 'old-valid-id.jpg',
            'file_path' => 'technician-documents/old-valid-id.jpg',
            'status' => 'rejected',
        ],
        [
            'type' => 'credentials',
            'label' => 'old-credentials.pdf',
            'file_path' => 'technician-documents/old-credentials.pdf',
            'status' => 'rejected',
        ],
    ]);

    Livewire::actingAs($admin)
        ->test(ModulePage::class, ['module' => 'technician-verification'])
        ->call('requestConfirmation', 'reject-verification', $technician->technicianVerification->id)
        ->set('confirmationReason', $reason)
        ->call('executeConfirmedAction');

    expect($technician->fresh()->account_status)->toBe(User::ACCOUNT_REJECTED)
        ->and($technician->technicianVerification->fresh()->decision_reason)->toBe($reason);
    Notification::assertSentTo(
        $technician,
        TechnicianApplicationDecisionNotification::class,
        fn (TechnicianApplicationDecisionNotification $notification): bool => $notification->verification->decision_reason === $reason
            && str_contains($notification->toMail($technician)->render(), $reason),
    );

    $technician->refresh();

    $this->actingAs($technician)
        ->get(route('technician.module'))
        ->assertOk()
        ->assertSee('Application needs correction')
        ->assertSee($reason)
        ->assertSee('Personal information')
        ->assertSee('Verification documents')
        ->assertSee('old-valid-id.jpg')
        ->assertSee('old-credentials.pdf')
        ->assertSee('name="valid_id"', false)
        ->assertSee('name="credentials"', false)
        ->assertSee('Resubmit application');

    $this->actingAs($technician)
        ->post(route('technician.application.resubmit'), [
            'first_name' => 'Maria',
            'middle_name' => 'Santos',
            'surname' => 'Reyes',
            'phone' => '09179999999',
            'service_area' => 'Makati City',
            'years_experience' => 6,
            'service_type' => 'walkin',
            'shop_name' => 'Verified Makati Repair Shop',
            'service_categories' => ['plumbing'],
            'valid_id' => UploadedFile::fake()->create('clear-valid-id.jpg', 512, 'image/jpeg'),
            'credentials' => UploadedFile::fake()->create('clear-credentials.pdf', 512, 'application/pdf'),
            'resubmission_notes' => 'Updated the shop name and service address to the correct registered details.',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('technician.module'));

    $technician->refresh();
    $verification = $technician->technicianVerification->fresh();
    $validId = $verification->documents()->where('type', 'valid_id')->firstOrFail();
    $credentials = $verification->documents()->where('type', 'credentials')->firstOrFail();

    expect($technician->account_status)->toBe(User::ACCOUNT_REVIEW_PENDING)
        ->and($technician->name)->toBe('Maria Santos Reyes')
        ->and($technician->first_name)->toBe('Maria')
        ->and($technician->middle_name)->toBe('Santos')
        ->and($technician->surname)->toBe('Reyes')
        ->and($verification->status)->toBe('submitted')
        ->and($verification->resubmission_count)->toBe(1)
        ->and($verification->shop_name)->toBe('Verified Makati Repair Shop')
        ->and($verification->address)->toBe('Makati City')
        ->and($verification->resubmission_notes)->toContain('Updated the shop name')
        ->and($validId->label)->toBe('clear-valid-id.jpg')
        ->and($validId->status)->toBe('pending')
        ->and($credentials->label)->toBe('clear-credentials.pdf')
        ->and($credentials->status)->toBe('pending');

    Storage::disk('local')->assertExists($validId->file_path);
    Storage::disk('local')->assertExists($credentials->file_path);
    Storage::disk('local')->assertMissing('technician-documents/old-valid-id.jpg');
    Storage::disk('local')->assertMissing('technician-documents/old-credentials.pdf');
});

test('technician resubmission rejects unsupported document files', function () {
    Storage::fake('local');
    $technician = onboardingTechnician(User::ACCOUNT_REJECTED, 'rejected');

    $this->actingAs($technician)
        ->post(route('technician.application.resubmit'), [
            'first_name' => 'Juan',
            'surname' => 'Dela Cruz',
            'phone' => '09171234567',
            'service_area' => 'Quezon City',
            'years_experience' => 4,
            'service_type' => 'home',
            'service_categories' => ['plumbing'],
            'credentials' => UploadedFile::fake()->create('credentials.exe', 64, 'application/octet-stream'),
            'resubmission_notes' => 'I uploaded a corrected credentials document for another review.',
        ])
        ->assertSessionHasErrors('credentials');

    expect($technician->fresh()->account_status)->toBe(User::ACCOUNT_REJECTED)
        ->and($technician->technicianVerification->fresh()->status)->toBe('rejected');
});

test('suspended technicians remain blocked from the technician workspace', function () {
    $technician = User::factory()->create([
        'role' => 'technician',
        'account_status' => 'suspended',
    ]);

    $this->actingAs($technician)
        ->get(route('technician.module'))
        ->assertForbidden();
});
