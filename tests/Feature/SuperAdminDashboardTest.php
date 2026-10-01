<?php

use App\Models\Booking;
use App\Models\ServiceCatalog;
use App\Models\TechnicianVerification;
use App\Models\User;
use Illuminate\Support\Facades\DB;

test('super admins can see the FixTrack operations overview', function () {
    $superAdmin = User::factory()->create(['role' => 'superadmin']);

    $response = $this->actingAs($superAdmin)->get(route('admin.dashboard'));

    $response->assertOk();

    foreach ([
        'Booking activity',
        'Booking status',
        'Needs attention',
        'Recent bookings',
        'Top services',
        'System status',
    ] as $label) {
        $response->assertSee($label);
    }

    $response->assertSee('admin-dashboard-reference', false)
        ->assertSee('data-admin-workspace', false)
        ->assertSee('Administrator Control Center')
        ->assertSee('Manage all users')
        ->assertSee('Platform settings')
        ->assertSee('data-admin-access-overview', false)
        ->assertDontSee('Active bookings');
});

test('super admin dashboard renders booking activity lines and status segments', function () {
    $superAdmin = User::factory()->create(['role' => 'superadmin']);
    $createdAt = now()->subDay();

    foreach (['in_progress', 'completed', 'cancelled'] as $index => $status) {
        DB::table('bookings')->insert([
            'user_id' => $superAdmin->id,
            'reference' => 'FT-CHART-00'.($index + 1),
            'customer_name' => 'Chart Customer '.($index + 1),
            'customer_phone' => '0917000000'.($index + 1),
            'service_type' => 'aircon',
            'booking_type' => 'scheduled',
            'status' => $status,
            'address' => 'Chart address',
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);
    }

    $this->actingAs($superAdmin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('data-super-admin-booking-activity-chart', false)
        ->assertSee('data-super-admin-booking-series="active"', false)
        ->assertSee('data-super-admin-booking-series="completed"', false)
        ->assertSee('data-super-admin-booking-series="cancelled"', false)
        ->assertSee('data-super-admin-booking-status-chart', false)
        ->assertSee('data-super-admin-status-ring="waiting"', false)
        ->assertSee('data-super-admin-status-ring="active"', false)
        ->assertSee('data-super-admin-status-ring="completed"', false)
        ->assertSee('data-super-admin-status-total', false)
        ->assertSee('data-super-admin-status-percentage="completed"', false)
        ->assertSee('Booking status chart with 3 bookings: 33 percent completed and 33 percent cancelled or no-show')
        ->assertSee('stroke-dasharray', false);
});

test('dashboard statistics show a useful empty state when no recent bookings exist', function () {
    $superAdmin = User::factory()->create(['role' => 'superadmin']);

    $this->actingAs($superAdmin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('data-super-admin-booking-activity-empty', false)
        ->assertSee('No booking activity in the last 7 days')
        ->assertSee('Start receiving bookings to see daily trends here.')
        ->assertSee('data-super-admin-status-total', false)
        ->assertSee('Total bookings');
});

test('staff dashboard reflects related bookings services and technician reviews', function () {
    $staff = User::factory()->create(['role' => 'staff']);
    $customer = User::factory()->create(['role' => 'customer', 'name' => 'Account Holder']);
    $technician = User::factory()->create(['role' => 'technician', 'name' => 'Review Applicant']);

    ServiceCatalog::query()->create([
        'code' => 'device-repair',
        'name' => 'Device repair',
        'category' => 'Electronics',
        'is_active' => true,
        'description' => 'Device service for dashboard testing.',
    ]);

    $pendingBooking = Booking::query()->create([
        'user_id' => $customer->id,
        'reference' => 'FT-DASH-REVIEW',
        'customer_name' => 'Booking Contact',
        'customer_phone' => '09170000000',
        'service_type' => 'device-repair',
        'booking_type' => 'scheduled',
        'status' => 'pending',
        'address' => 'Test address',
    ]);
    $pendingBooking->forceFill(['created_at' => now()->subMinutes(20)])->save();

    Booking::query()->create([
        'user_id' => $customer->id,
        'reference' => 'FT-DASH-NO-SHOW',
        'customer_name' => 'Booking Contact',
        'customer_phone' => '09170000000',
        'service_type' => 'device-repair',
        'booking_type' => 'scheduled',
        'status' => 'no_show',
        'address' => 'Test address',
    ]);

    TechnicianVerification::query()->create([
        'user_id' => $technician->id,
        'status' => 'submitted',
        'risk_level' => 'low',
        'service_categories' => ['device-repair'],
        'years_experience' => 2,
        'submitted_at' => now(),
    ]);

    $this->actingAs($staff)
        ->get(route('staff.dashboard'))
        ->assertOk()
        ->assertSee('FT-DASH-REVIEW')
        ->assertSee('Booking Contact')
        ->assertDontSee('Account Holder')
        ->assertSee('Device repair')
        ->assertSee('Booking status chart with 2 bookings: 0 percent completed and 50 percent cancelled or no-show')
        ->assertSee('Dispatch overdue')
        ->assertSee('Review Applicant')
        ->assertSee('Core records connected')
        ->assertSee('Staff Operations Dashboard')
        ->assertSee('Today’s operations')
        ->assertSee('Pending bookings')
        ->assertSee('Unassigned jobs')
        ->assertSee('Waiting walk-ins')
        ->assertSee('Open support tickets')
        ->assertSee('Technician reviews')
        ->assertSee('data-staff-operations-summary', false)
        ->assertDontSee('data-admin-access-overview', false)
        ->assertDontSee(route('staff.module', ['module' => 'system-health']))
        ->assertSee('data-admin-dashboard-view-bookings', false)
        ->assertSee('href="'.route('staff.module', ['module' => 'technician-verification']).'"', false);
});

test('dashboard booking alerts open their booking even when newer records fill the registry', function () {
    $staff = User::factory()->create(['role' => 'staff']);
    $customer = User::factory()->create(['role' => 'customer']);

    $booking = Booking::query()->create([
        'user_id' => $customer->id,
        'reference' => 'FT-OLDER-PENDING',
        'customer_name' => 'Alert Customer',
        'customer_phone' => '09170000000',
        'service_type' => 'aircon',
        'booking_type' => 'scheduled',
        'status' => 'pending',
        'address' => 'Test address',
    ]);
    $booking->forceFill(['created_at' => now()->subDay()])->save();

    foreach (range(1, 13) as $index) {
        Booking::query()->create([
            'user_id' => $customer->id,
            'reference' => 'FT-NEWER-'.str_pad((string) $index, 2, '0', STR_PAD_LEFT),
            'customer_name' => 'Recent Customer',
            'customer_phone' => '09170000000',
            'service_type' => 'aircon',
            'booking_type' => 'scheduled',
            'status' => 'cancelled',
            'address' => 'Test address',
        ]);
    }

    $bookingLink = route('staff.module', ['module' => 'service-bookings', 'booking' => $booking->id]);

    $this->actingAs($staff)
        ->get(route('staff.dashboard'))
        ->assertOk()
        ->assertSee('href="'.$bookingLink.'"', false);

    $this->actingAs($staff)
        ->get(route('staff.module', ['module' => 'service-bookings']))
        ->assertOk()
        ->assertDontSee('FT-OLDER-PENDING');

    $this->actingAs($staff)
        ->get($bookingLink)
        ->assertOk()
        ->assertSee('FT-OLDER-PENDING');
});

test('super admin dashboard exposes advanced chart details without clipped legends', function () {
    $superAdmin = User::factory()->create(['role' => 'superadmin']);
    Booking::query()->create([
        'user_id' => $superAdmin->id,
        'reference' => 'FT-CHART-DETAILS',
        'customer_name' => 'Chart Customer',
        'customer_phone' => '09170000000',
        'service_type' => 'aircon',
        'booking_type' => 'scheduled',
        'status' => 'in_progress',
        'address' => 'Chart address',
    ]);

    $this->actingAs($superAdmin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('data-super-admin-chart-summary', false)
        ->assertSee('data-super-admin-booking-series-type="smooth"', false)
        ->assertSee('data-super-admin-booking-area="active"', false)
        ->assertSee('data-super-admin-booking-tooltip', false)
        ->assertSee('data-super-admin-chart-y-tick="middle" data-value="1"', false)
        ->assertSee('data-super-admin-chart-y-tick="top" data-value="2"', false)
        ->assertSee('data-super-admin-booking-activity-layout="reference"', false)
        ->assertSee('data-super-admin-status-chart-layout="reference"', false)
        ->assertSee('data-super-admin-status-legend', false)
        ->assertSee('data-super-admin-status-layout="reference"', false)
        ->assertSee('min-h-[15.625rem]', false)
        ->assertSee('data-super-admin-status-chart-center="summary"', false);
});

test('regular users cannot access the Super Admin dashboard', function () {
    $user = User::factory()->create(['role' => 'customer']);

    $this->actingAs($user)
        ->get(route('admin.dashboard'))
        ->assertForbidden();
});

test('super admins are sent to their dashboard after signing in', function () {
    $superAdmin = User::factory()->create(['role' => 'superadmin']);

    $this->actingAs($superAdmin)
        ->get(route('dashboard'))
        ->assertRedirect(route('admin.dashboard'));
});

test('super admin pages expose the shared sidebar and section action menus', function () {
    $superAdmin = User::factory()->create(['role' => 'superadmin']);
    DB::table('bookings')->insert([
        'user_id' => $superAdmin->id,
        'reference' => 'FT-DASHBOARD-001',
        'customer_name' => 'Dashboard Customer',
        'customer_phone' => '09170000000',
        'service_type' => 'aircon',
        'booking_type' => 'scheduled',
        'status' => 'pending',
        'address' => 'Test address',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->actingAs($superAdmin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertDontSee('Search modules')
        ->assertSee('data-flux-sidebar-collapse', false)
        ->assertSee('data-admin-sidebar-navigation', false)
        ->assertSee('data-super-admin-section-menu', false)
        ->assertSee('data-super-admin-dashboard-table', false)
        ->assertSee('whitespace-normal break-words', false);

    $this->actingAs($superAdmin)
        ->get('/admin/system-health')
        ->assertOk()
        ->assertSee('data-flux-sidebar-collapse', false)
        ->assertSee('data-admin-sidebar-navigation', false)
        ->assertSee('Service checks')
        ->assertSee('Refresh section')
        ->assertSee('data-super-admin-section-menu', false);
});

test('super admin module icons remain visible when the sidebar is collapsed', function () {
    $superAdmin = User::factory()->create(['role' => 'superadmin']);

    $html = $this->actingAs($superAdmin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->getContent();

    expect($html)->toContain('data-super-admin-sidebar-item="service-bookings"');
    expect($html)->toContain('in-data-flux-sidebar-collapsed-desktop:size-11');
    expect($html)->toContain('in-data-flux-sidebar-collapsed-desktop:!flex');
});
