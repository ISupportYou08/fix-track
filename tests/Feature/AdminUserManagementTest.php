<?php

use App\Livewire\SuperAdmin\ModulePage;
use App\Models\AuditLog;
use App\Models\Booking;
use App\Models\TechnicianVerification;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

test('administrator can view every account type in the user directory', function () {
    $administrator = User::factory()->create(['role' => 'superadmin']);
    $staff = User::factory()->create(['role' => 'staff', 'email' => 'team@fixtrack.test']);
    $technician = User::factory()->create(['role' => 'technician', 'email' => 'tech@fixtrack.test']);
    $customer = User::factory()->create(['role' => 'customer', 'email' => 'customer@fixtrack.test']);

    $this->actingAs($administrator)
        ->get(route('admin.module', ['module' => 'users-roles']))
        ->assertOk()
        ->assertSee($administrator->email)
        ->assertSee($staff->email)
        ->assertSee($technician->email)
        ->assertSee($customer->email)
        ->assertSee('Create staff account')
        ->assertSee('data-admin-user-directory', false);
});

test('administrator can search and filter the user directory', function () {
    $administrator = User::factory()->create(['role' => 'superadmin']);
    User::factory()->create(['role' => 'staff', 'name' => 'Matched Staff', 'email' => 'matched@fixtrack.test']);
    User::factory()->create(['role' => 'customer', 'name' => 'Hidden Customer', 'email' => 'hidden@fixtrack.test']);

    Livewire::actingAs($administrator)
        ->test(ModulePage::class, ['module' => 'users-roles'])
        ->set('userSearch', 'matched@fixtrack.test')
        ->set('userRoleFilter', 'staff')
        ->assertSee('Matched Staff')
        ->assertDontSee('Hidden Customer');
});

test('administrator can create a verified active staff account', function () {
    $administrator = User::factory()->create(['role' => 'superadmin']);

    Livewire::actingAs($administrator)
        ->test(ModulePage::class, ['module' => 'users-roles'])
        ->call('openStaffEditor')
        ->set('staffName', 'Front Desk Staff')
        ->set('staffEmail', 'frontdesk@fixtrack.test')
        ->set('staffPhone', '+639171234567')
        ->set('staffPassword', 'FixTrack123!')
        ->set('staffPasswordConfirmation', 'FixTrack123!')
        ->call('createStaff')
        ->assertHasNoErrors()
        ->assertSet('showStaffEditor', false);

    $staff = User::query()->where('email', 'frontdesk@fixtrack.test')->firstOrFail();

    expect($staff->role)->toBe('staff')
        ->and($staff->account_status)->toBe('active')
        ->and($staff->email_verified_at)->not->toBeNull()
        ->and(Hash::check('FixTrack123!', $staff->password))->toBeTrue()
        ->and(AuditLog::query()->where('action', 'staff.created')->where('target_id', $staff->id)->exists())->toBeTrue();
});

test('staff cannot open administrator account and platform modules', function (string $module) {
    $staff = User::factory()->create(['role' => 'staff']);

    $this->actingAs($staff)
        ->get(route('staff.module', ['module' => $module]))
        ->assertForbidden();
})->with(['system-health', 'users-roles', 'audit-logs', 'platform-settings']);

test('staff cannot invoke administrator system and settings actions directly', function () {
    $staff = User::factory()->create(['role' => 'staff']);

    Livewire::actingAs($staff)
        ->test(ModulePage::class, ['module' => 'service-bookings'])
        ->call('runSystemAction', 'clear-cache')
        ->assertForbidden();

    Livewire::actingAs($staff)
        ->test(ModulePage::class, ['module' => 'service-bookings'])
        ->call('initializePlatformSettings')
        ->assertForbidden();
});

test('staff sidebar keeps operational modules and hides administrator modules', function () {
    $staff = User::factory()->create(['role' => 'staff']);

    $response = $this->actingAs($staff)->get(route('staff.dashboard'));

    $response->assertOk();

    foreach (['dashboard', 'technician-verification', 'service-bookings', 'dispatch-monitor', 'walk-in-queue', 'payments-revenue', 'ratings-reviews', 'reports-analytics', 'support-disputes', 'service-catalog'] as $module) {
        $response->assertSee('data-super-admin-sidebar-item="'.$module.'"', false);
    }

    foreach (['system-health', 'users-roles', 'audit-logs', 'platform-settings'] as $module) {
        $response->assertDontSee('data-super-admin-sidebar-item="'.$module.'"', false);
    }

    $response
        ->assertSee('data-staff-workspace-label', false)
        ->assertSee('Staff Workspace')
        ->assertSeeInOrder(['Workspace', 'Service Operations', 'Technicians', 'Customer Care', 'Finance', 'Tools'])
        ->assertSee('Dispatch &amp; Availability', false)
        ->assertSee('text-base', false)
        ->assertSee('href="'.route('profile.edit').'"', false)
        ->assertDontSee('href="'.route('staff.module', ['module' => 'platform-settings']).'"', false);
});

test('staff sidebar displays counters for pending operational work', function () {
    $staff = User::factory()->create(['role' => 'staff']);
    $customer = User::factory()->create(['role' => 'customer']);
    $technician = User::factory()->create(['role' => 'technician']);

    TechnicianVerification::query()->create([
        'user_id' => $technician->id,
        'status' => 'submitted',
        'risk_level' => 'low',
        'service_categories' => ['plumbing'],
        'years_experience' => 2,
        'submitted_at' => now(),
    ]);
    Booking::query()->create([
        'user_id' => $customer->id,
        'reference' => 'FT-STAFF-NAV',
        'customer_name' => $customer->name,
        'customer_phone' => '+639171234567',
        'service_type' => 'plumbing',
        'booking_type' => 'quick',
        'status' => 'pending',
        'address' => 'Quezon City',
    ]);

    $this->actingAs($staff)
        ->get(route('staff.dashboard'))
        ->assertOk()
        ->assertSee('data-staff-nav-badge="technician-verification"', false)
        ->assertSee('data-staff-nav-badge="service-bookings"', false)
        ->assertSee('data-staff-nav-badge="dispatch-monitor"', false);
});

test('administrator cannot change or revoke their own account access', function () {
    $administrator = User::factory()->create(['role' => 'superadmin']);

    Livewire::actingAs($administrator)
        ->test(ModulePage::class, ['module' => 'users-roles'])
        ->call('updateUserRole', $administrator->id, 'staff')
        ->assertStatus(422);

    Livewire::actingAs($administrator)
        ->test(ModulePage::class, ['module' => 'users-roles'])
        ->call('revokeUserSessions', $administrator->id)
        ->assertStatus(422);

    expect($administrator->refresh()->role)->toBe('superadmin');
});
