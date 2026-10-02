<?php

use App\Models\User;
use Laravel\Fortify\Features;
use Laravel\Passkeys\Passkey;
use Laravel\Passkeys\Passkeys;

test('operations portal roots direct guests to the matching sign in page', function (string $portalRoute, string $loginRoute) {
    $this->get(route($portalRoute))
        ->assertRedirect(route($loginRoute));
})->with([
    ['staff.index', 'staff.login'],
    ['admin.index', 'admin.login'],
]);

test('operations portal roots direct authenticated users to their dashboard', function (string $role, string $portalRoute, string $dashboardRoute) {
    $operationsUser = User::factory()->create([
        'role' => $role,
        'account_status' => 'active',
    ]);

    $this->actingAs($operationsUser)
        ->get(route($portalRoute))
        ->assertRedirect(route($dashboardRoute));
})->with([
    ['staff', 'staff.index', 'staff.dashboard'],
    ['superadmin', 'admin.index', 'admin.dashboard'],
]);

test('operations portal roots reject an authenticated account from another role', function (string $role, string $portalRoute) {
    $user = User::factory()->create([
        'role' => $role,
        'account_status' => 'active',
    ]);

    $this->actingAs($user)
        ->get(route($portalRoute))
        ->assertForbidden()
        ->assertSee('Access restricted')
        ->assertSee('Go to my dashboard')
        ->assertSee('Sign in with another account');
})->with([
    ['customer', 'staff.index'],
    ['technician', 'staff.index'],
    ['superadmin', 'staff.index'],
    ['customer', 'admin.index'],
    ['technician', 'admin.index'],
    ['staff', 'admin.index'],
]);

test('staff and administrator login screens are separate from public sign in', function () {
    $this->get(route('staff.login'))
        ->assertOk()
        ->assertSee('Welcome,')
        ->assertSee('Staff Sign In')
        ->assertSee('Staff Email')
        ->assertSee('data-test="staff-login-button"', false)
        ->assertSee(route('staff.login.store'))
        ->assertDontSee('Admin Sign In');

    $this->get(route('admin.login'))
        ->assertOk()
        ->assertSee('Welcome,')
        ->assertSee('Administrator')
        ->assertSee('Admin Sign In')
        ->assertSee('Admin Email')
        ->assertSee('data-test="admin-login-button"', false)
        ->assertSee(route('admin.login.store'));

    $this->get(route('login'))
        ->assertOk()
        ->assertDontSee('Staff Sign In')
        ->assertDontSee('Admin Sign In');
});

test('operations login failures display a translated credential message', function (string $portalRoute) {
    $response = $this->post(route($portalRoute), [
        'email' => 'unknown@fixtrack.test',
        'password' => 'incorrect-password',
    ]);

    $response->assertSessionHasErrors([
        'email' => __('auth.failed'),
    ]);

    expect(__('auth.failed'))->toBe('These credentials do not match our records.');
})->with([
    'staff.login.store',
    'admin.login.store',
]);

test('staff can authenticate only through the staff portal', function () {
    $staff = User::factory()->create([
        'role' => 'staff',
        'account_status' => 'active',
    ]);

    $this->post(route('admin.login.store'), [
        'email' => $staff->email,
        'password' => 'password',
    ])->assertSessionHasErrors('email');

    $response = $this->post(route('staff.login.store'), [
        'email' => $staff->email,
        'password' => 'password',
    ]);

    $response->assertSessionHasNoErrors()->assertRedirect(route('dashboard', absolute: false));
    $this->assertAuthenticatedAs($staff);
});

test('super administrators can authenticate only through the administrator portal', function () {
    $administrator = User::factory()->create([
        'role' => 'superadmin',
        'account_status' => 'active',
    ]);

    $this->post(route('staff.login.store'), [
        'email' => $administrator->email,
        'password' => 'password',
    ])->assertSessionHasErrors('email');

    $response = $this->post(route('admin.login.store'), [
        'email' => $administrator->email,
        'password' => 'password',
    ]);

    $response->assertSessionHasNoErrors()->assertRedirect(route('dashboard', absolute: false));
    $this->assertAuthenticatedAs($administrator);
});

test('public accounts cannot authenticate through staff or administrator portals', function (string $role, string $portalRoute) {
    $user = User::factory()->create([
        'role' => $role,
        'account_status' => 'active',
    ]);

    $this->post(route($portalRoute), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertSessionHasErrors('email');

    $this->assertGuest();
})->with([
    ['customer', 'staff.login.store'],
    ['technician', 'staff.login.store'],
    ['customer', 'admin.login.store'],
    ['technician', 'admin.login.store'],
]);

test('operations accounts cannot authenticate through public password or passkey login', function (string $role) {
    $operationsUser = User::factory()->create([
        'role' => $role,
        'account_status' => 'active',
    ]);

    $this->post(route('login.store'), [
        'email' => $operationsUser->email,
        'password' => 'password',
    ])->assertSessionHasErrors('email');

    expect(Passkeys::allowsLogin(request(), (new Passkey)->setRelation('user', $operationsUser)))->toBeFalse();
    $this->assertGuest();
})->with(['staff', 'superadmin']);

test('inactive operations accounts cannot authenticate through their portal', function (string $role, string $portalRoute) {
    $operationsUser = User::factory()->create([
        'role' => $role,
        'account_status' => 'suspended',
    ]);

    $this->post(route($portalRoute), [
        'email' => $operationsUser->email,
        'password' => 'password',
    ])->assertSessionHasErrors('email');

    $this->assertGuest();
})->with([
    ['staff', 'staff.login.store'],
    ['superadmin', 'admin.login.store'],
]);

test('staff portal keeps Fortify two factor authentication', function () {
    $this->skipUnlessFortifyHas(Features::twoFactorAuthentication());

    Features::twoFactorAuthentication([
        'confirm' => true,
        'confirmPassword' => true,
    ]);

    $staff = User::factory()->withTwoFactor()->create([
        'role' => 'staff',
        'account_status' => 'active',
    ]);

    $response = $this->post(route('staff.login.store'), [
        'email' => $staff->email,
        'password' => 'password',
    ]);

    $response->assertRedirect(route('two-factor.login'));
    $this->assertGuest();
    expect(session('login.id'))->toBe($staff->id);
});

test('staff login endpoint is rate limited', function () {
    $staff = User::factory()->create([
        'role' => 'staff',
        'account_status' => 'active',
    ]);

    foreach (range(1, 5) as $attempt) {
        $this->post(route('staff.login.store'), [
            'email' => $staff->email,
            'password' => 'wrong-password-'.$attempt,
        ])->assertSessionHasErrors('email');
    }

    $this->post(route('staff.login.store'), [
        'email' => $staff->email,
        'password' => 'still-wrong',
    ])->assertTooManyRequests();
});
