<?php

use App\Livewire\SuperAdmin\ModulePage;
use App\Models\AuditLog;
use App\Models\Booking;
use App\Models\TechnicianVerification;
use App\Models\User;
use App\Notifications\BookingStatusChanged;
use App\Notifications\TechnicianAccountStatusNotification;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

function approvedTechnicianAccount(): User
{
    $technician = User::factory()->create([
        'role' => 'technician',
        'account_status' => 'active',
        'email_verified_at' => now(),
    ]);

    TechnicianVerification::query()->create([
        'user_id' => $technician->id,
        'status' => 'approved',
        'risk_level' => 'low',
        'service_categories' => ['plumbing'],
        'years_experience' => 3,
        'service_area' => 'Quezon City',
        'service_type' => 'home',
        'submitted_at' => now(),
        'reviewed_at' => now(),
    ]);

    return $technician;
}

test('verified technician details show account actions without application decisions or a footer close button', function () {
    $admin = User::factory()->create(['role' => 'superadmin']);
    $technician = approvedTechnicianAccount();

    $this->actingAs($admin)
        ->get(route('admin.module', ['module' => 'technician-verification', 'filter' => 'verified']))
        ->assertOk()
        ->assertSee($technician->name);

    Livewire::actingAs($admin)
        ->test(ModulePage::class, ['module' => 'technician-verification'])
        ->set('verificationFilter', 'verified')
        ->call('openVerificationDetails', $technician->technicianVerification->id)
        ->assertSee('Verified')
        ->assertSee('Suspend account')
        ->assertSee('Ban account')
        ->assertSee('Close technician details')
        ->assertDontSee('>Close</button>', false)
        ->assertDontSee('Decline')
        ->assertDontSee('Resubmit')
        ->assertDontSee('Accept');
});

test('unapproved technician details only show application review actions', function () {
    $admin = User::factory()->create(['role' => 'superadmin']);
    $technician = approvedTechnicianAccount();
    $technician->technicianVerification->update(['status' => 'submitted']);

    Livewire::actingAs($admin)
        ->test(ModulePage::class, ['module' => 'technician-verification'])
        ->call('openVerificationDetails', $technician->technicianVerification->id)
        ->assertSee('Decline')
        ->assertSee('Resubmit')
        ->assertSee('Accept')
        ->assertDontSee('Suspend account')
        ->assertDontSee('Ban account');
});

test('staff can review technician applications but final decisions remain with administrators', function () {
    $staff = User::factory()->create(['role' => 'staff']);
    $technician = approvedTechnicianAccount();
    $verification = $technician->technicianVerification;
    $verification->update(['status' => 'submitted']);

    Livewire::actingAs($staff)
        ->test(ModulePage::class, ['module' => 'technician-verification'])
        ->call('openVerificationDetails', $verification->id)
        ->assertSee('Mark under review')
        ->assertSee('Request resubmission')
        ->assertDontSee('Accept')
        ->assertDontSee('Decline')
        ->call('updateVerificationStatus', $verification->id, 'under_review')
        ->assertHasNoErrors();

    expect($verification->fresh()->status)->toBe('under_review');

    Livewire::actingAs($staff)
        ->test(ModulePage::class, ['module' => 'technician-verification'])
        ->call('requestConfirmation', 'approve-verification', $verification->id)
        ->assertForbidden();
});

test('staff cannot restrict or restore verified technician accounts', function () {
    $staff = User::factory()->create(['role' => 'staff']);
    $technician = approvedTechnicianAccount();
    $verification = $technician->technicianVerification;

    Livewire::actingAs($staff)
        ->test(ModulePage::class, ['module' => 'technician-verification'])
        ->set('verificationFilter', 'verified')
        ->call('openVerificationDetails', $verification->id)
        ->assertSee('Administrator approval required')
        ->assertDontSee('Suspend account')
        ->assertDontSee('Ban account')
        ->call('requestConfirmation', 'suspend-technician', $verification->id)
        ->assertForbidden();

    expect($technician->fresh()->account_status)->toBe('active');
});

test('suspension needs a message and confirmation and returns assigned jobs to matching', function () {
    Notification::fake();
    $admin = User::factory()->create(['role' => 'superadmin']);
    $customer = User::factory()->create(['role' => 'customer']);
    $technician = approvedTechnicianAccount();
    $verification = $technician->technicianVerification;
    $booking = Booking::query()->create([
        'user_id' => $customer->id,
        'assigned_technician_id' => $technician->id,
        'reference' => 'PEN-1001',
        'customer_name' => $customer->name,
        'customer_phone' => '09170000000',
        'service_type' => 'plumbing',
        'booking_type' => 'quick',
        'status' => 'assigned',
        'address' => 'Quezon City',
        'description' => 'Repair request.',
    ]);
    $message = 'Repeatedly missed assigned appointments without contacting customers.';
    $technician->setRememberToken('old-remember-token');
    $technician->save();
    DB::table('sessions')->insert([
        'id' => 'technician-session-before-suspension',
        'user_id' => $technician->id,
        'payload' => '',
        'last_activity' => now()->timestamp,
    ]);

    $component = Livewire::actingAs($admin)
        ->test(ModulePage::class, ['module' => 'technician-verification'])
        ->set('verificationFilter', 'verified')
        ->call('requestConfirmation', 'suspend-technician', $verification->id)
        ->assertSet('showConfirmation', true)
        ->assertSee('Penalty message')
        ->assertSee('Suspension duration (days)')
        ->call('cancelConfirmation')
        ->assertSet('showConfirmation', false);

    expect($technician->fresh()->account_status)->toBe('active');

    $component
        ->call('requestConfirmation', 'suspend-technician', $verification->id)
        ->call('executeConfirmedAction')
        ->assertHasErrors(['reason' => 'required'])
        ->set('confirmationReason', 'Too short')
        ->call('executeConfirmedAction')
        ->assertHasErrors(['reason' => 'min'])
        ->set('confirmationReason', $message)
        ->call('executeConfirmedAction')
        ->assertHasErrors(['suspension_days' => 'required'])
        ->set('suspensionDays', '0')
        ->call('executeConfirmedAction')
        ->assertHasErrors(['suspension_days' => 'between'])
        ->set('suspensionDays', '366')
        ->call('executeConfirmedAction')
        ->assertHasErrors(['suspension_days' => 'between'])
        ->set('suspensionDays', '3')
        ->call('executeConfirmedAction')
        ->assertSet('showConfirmation', false)
        ->assertSee('Suspended');

    $technician->refresh();
    $booking->refresh();

    expect($technician->account_status)->toBe('suspended')
        ->and(abs($technician->suspended_until->diffInSeconds(now()->addDays(3))) < 2)->toBeTrue()
        ->and($technician->availability_status)->toBe('offline')
        ->and($technician->getRememberToken())->not->toBe('old-remember-token')
        ->and($verification->fresh()->status)->toBe('approved')
        ->and($booking->status)->toBe('matching')
        ->and($booking->assigned_technician_id)->toBeNull()
        ->and($booking->statusHistory()->where('to_status', 'matching')->exists())->toBeTrue();

    $audit = AuditLog::query()->where('action', 'technician.account_status_updated')->where('target_id', $technician->id)->firstOrFail();
    expect($audit->details['reason'])->toBe($message)
        ->and($audit->details['suspension_days'])->toBe(3)
        ->and($audit->details['requeued_bookings'])->toBe(1);
    expect(DB::table('sessions')->where('user_id', $technician->id)->exists())->toBeFalse();

    Notification::assertSentTo($technician, TechnicianAccountStatusNotification::class, fn (TechnicianAccountStatusNotification $notification): bool => $notification->status === 'suspended'
        && $notification->reason === $message
        && $notification->suspendedUntil?->format('Y-m-d H:i:s') === $technician->suspended_until->format('Y-m-d H:i:s')
        && str_contains($notification->toMail($technician)->render(), $message));
    Notification::assertSentTo($customer, BookingStatusChanged::class);

    $this->actingAs($technician->fresh())->get(route('technician.module'))->assertForbidden();
});

test('expired suspensions restore automatically and leave bans untouched', function () {
    Notification::fake();
    $admin = User::factory()->create(['role' => 'superadmin']);
    $technician = approvedTechnicianAccount();
    $bannedTechnician = approvedTechnicianAccount();

    Livewire::actingAs($admin)
        ->test(ModulePage::class, ['module' => 'technician-verification'])
        ->call('requestConfirmation', 'suspend-technician', $technician->technicianVerification->id)
        ->set('confirmationReason', 'Missed scheduled appointments without advance notice.')
        ->set('suspensionDays', '2')
        ->call('executeConfirmedAction');
    $bannedTechnician->update(['account_status' => User::ACCOUNT_BANNED, 'suspended_until' => now()->subDay()]);

    $this->travel(1)->days();
    Artisan::call('technicians:expire-suspensions');
    expect($technician->fresh()->account_status)->toBe('suspended');

    $this->travel(1)->days();
    Artisan::call('technicians:expire-suspensions');
    $technician->refresh();
    expect($technician->account_status)->toBe('active')
        ->and($technician->suspended_until)->toBeNull()
        ->and($technician->availability_status)->toBe('offline')
        ->and($bannedTechnician->fresh()->account_status)->toBe(User::ACCOUNT_BANNED);
    expect(AuditLog::query()->where('target_id', $technician->id)->where('action', 'technician.account_status_updated')->count())->toBe(2);
    Notification::assertSentToTimes($technician, TechnicianAccountStatusNotification::class, 2);

    Artisan::call('technicians:expire-suspensions');
    expect(AuditLog::query()->where('target_id', $technician->id)->where('action', 'technician.account_status_updated')->count())->toBe(2);
});

test('expired technicians can sign in even when the scheduler has not run yet', function () {
    Notification::fake();
    $admin = User::factory()->create(['role' => 'superadmin']);
    $technician = approvedTechnicianAccount();

    Livewire::actingAs($admin)
        ->test(ModulePage::class, ['module' => 'technician-verification'])
        ->call('requestConfirmation', 'suspend-technician', $technician->technicianVerification->id)
        ->set('confirmationReason', 'Missed scheduled appointments without advance notice.')
        ->set('suspensionDays', '1')
        ->call('executeConfirmedAction');

    $this->travel(2)->days();
    Auth::logout();
    $this->post(route('login.store'), ['email' => $technician->email, 'password' => 'password'])
        ->assertSessionHasNoErrors();

    $this->assertAuthenticatedAs($technician);
    expect($technician->fresh()->account_status)->toBe('active')
        ->and($technician->fresh()->suspended_until)->toBeNull();
});

test('a suspended technician can be banned and a banned technician can be restored', function () {
    Notification::fake();
    $admin = User::factory()->create(['role' => 'superadmin']);
    $technician = approvedTechnicianAccount();
    $verification = $technician->technicianVerification;

    $component = Livewire::actingAs($admin)
        ->test(ModulePage::class, ['module' => 'technician-verification'])
        ->set('verificationFilter', 'verified')
        ->call('requestConfirmation', 'suspend-technician', $verification->id)
        ->set('confirmationReason', 'Failed to attend several assigned repair jobs.')
        ->set('suspensionDays', '7')
        ->call('executeConfirmedAction')
        ->call('openVerificationDetails', $verification->id)
        ->assertSee('Restore account')
        ->assertSee('Ban account')
        ->assertDontSee('Decline');

    $component
        ->call('requestConfirmation', 'ban-technician', $verification->id)
        ->assertDontSee('Suspension duration (days)')
        ->set('confirmationReason', 'Repeated policy violations after the prior suspension.')
        ->call('executeConfirmedAction')
        ->call('openVerificationDetails', $verification->id)
        ->assertSee('Banned')
        ->assertSee('Restore account')
        ->assertSee('Repeated policy violations after the prior suspension.')
        ->assertDontSee('Suspend account')
        ->assertDontSee('Ban account');

    expect($technician->fresh()->account_status)->toBe(User::ACCOUNT_BANNED)
        ->and($technician->fresh()->suspended_until)->toBeNull()
        ->and($verification->fresh()->status)->toBe('approved');
    $this->actingAs($technician->fresh())->get(route('technician.module'))->assertForbidden();
    $this->actingAs($admin);
    $technician->update(['availability_status' => 'available']);

    $component
        ->call('requestConfirmation', 'restore-technician', $verification->id)
        ->assertSee('Restoration message')
        ->assertDontSee('Suspension duration (days)')
        ->set('confirmationReason', 'Appeal reviewed and account access is restored.')
        ->call('executeConfirmedAction');

    $technician->refresh();
    expect($technician->account_status)->toBe('active')
        ->and($technician->availability_status)->toBe('offline')
        ->and($verification->fresh()->status)->toBe('approved');
    Notification::assertSentTo($technician, TechnicianAccountStatusNotification::class, fn (TechnicianAccountStatusNotification $notification): bool => $notification->status === 'active');
    $this->actingAs($technician)->get(route('technician.module'))->assertOk();
});

test('an active verified technician can be banned directly with a recorded message', function () {
    Notification::fake();
    $admin = User::factory()->create(['role' => 'superadmin']);
    $technician = approvedTechnicianAccount();
    $reason = 'Repeated serious violations of technician safety requirements.';

    Livewire::actingAs($admin)
        ->test(ModulePage::class, ['module' => 'technician-verification'])
        ->set('verificationFilter', 'verified')
        ->call('requestConfirmation', 'ban-technician', $technician->technicianVerification->id)
        ->set('confirmationReason', $reason)
        ->call('executeConfirmedAction')
        ->call('openVerificationDetails', $technician->technicianVerification->id)
        ->assertSee('Banned')
        ->assertSee($reason);

    expect($technician->fresh()->account_status)->toBe(User::ACCOUNT_BANNED)
        ->and($technician->technicianVerification->fresh()->status)->toBe('approved');
    Notification::assertSentTo($technician, TechnicianAccountStatusNotification::class, fn (TechnicianAccountStatusNotification $notification): bool => $notification->status === User::ACCOUNT_BANNED && $notification->reason === $reason);
});

test('technician review and account restrictions cannot be bypassed through older admin actions', function () {
    $admin = User::factory()->create(['role' => 'superadmin']);
    $technician = approvedTechnicianAccount();
    $verification = $technician->technicianVerification;

    Livewire::actingAs($admin)
        ->test(ModulePage::class, ['module' => 'technician-verification'])
        ->call('updateVerificationStatus', $verification->id, 'rejected', 'A long enough rejection reason.')
        ->assertStatus(422);

    Livewire::actingAs($admin)
        ->test(ModulePage::class, ['module' => 'users-roles'])
        ->call('updateUserStatus', $technician->id, 'suspended')
        ->assertStatus(422);

    Livewire::actingAs($admin)
        ->test(ModulePage::class, ['module' => 'users-roles'])
        ->call('updateUserStatus', $technician->id, 'active')
        ->assertStatus(422);

    $pendingTechnician = approvedTechnicianAccount();
    $pendingTechnician->technicianVerification->update(['status' => 'submitted']);
    $pendingTechnician->update(['account_status' => User::ACCOUNT_REVIEW_PENDING]);
    Livewire::actingAs($admin)
        ->test(ModulePage::class, ['module' => 'users-roles'])
        ->call('updateUserStatus', $pendingTechnician->id, 'active')
        ->assertStatus(422);

    expect($technician->fresh()->account_status)->toBe('active')
        ->and($verification->fresh()->status)->toBe('approved');
});
