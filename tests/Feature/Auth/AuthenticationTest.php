<?php

use App\Models\User;
use Database\Seeders\DemoAccountsSeeder;
use Illuminate\Support\Facades\Http;
use Laravel\Fortify\Features;
use Laravel\Passkeys\Passkey;
use Laravel\Passkeys\Passkeys;

test('login screen can be rendered', function () {
    $response = $this->get(route('login'));

    $response->assertOk()
        ->assertSee('customer-technician-sign-in')
        ->assertSee('sign-in-repair.jpg')
        ->assertSee('Online Service Request and Repair Tracking System')
        ->assertSee('Sign In')
        ->assertSee('sign-in-input')
        ->assertSee('juandelacruz@example.com')
        ->assertSee('Register Now')
        ->assertSee('Continue with Google')
        ->assertDontSee('Open the secure admin portal')
        ->assertSee(route('login.store'))
        ->assertSee(route('password.request'))
        ->assertSee(route('register'))
        ->assertSee(route('auth.google.redirect'));
});

test('users can authenticate using the login screen', function () {
    $user = User::factory()->create();

    $response = $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticated();
});

test('sample customer can authenticate using the published credentials', function () {
    $this->seed(DemoAccountsSeeder::class);
    $user = User::query()->where('email', 'user@fixtrack.test')->firstOrFail();

    $response = $this->post(route('login.store'), [
        'email' => 'user@fixtrack.test',
        'password' => 'FixTrack123!',
    ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticatedAs($user);
});

test('technician login marks the technician available', function () {
    $technician = User::factory()->create([
        'role' => 'technician',
        'availability_status' => 'offline',
    ]);

    $this->post(route('login.store'), [
        'email' => $technician->email,
        'password' => 'password',
    ]);

    expect($technician->fresh()->availability_status)->toBe('available');
});

test('suspended and banned technicians cannot sign in with password or passkey', function (string $status) {
    $technician = User::factory()->create([
        'role' => 'technician',
        'account_status' => $status,
    ]);

    $this->post(route('login.store'), [
        'email' => $technician->email,
        'password' => 'password',
    ])->assertSessionHasErrors('email');

    $this->assertGuest();
    expect(Passkeys::allowsLogin(request(), (new Passkey)->setRelation('user', $technician)))->toBeFalse();
})->with(['suspended', User::ACCOUNT_BANNED]);

test('technicians awaiting review can still sign in to complete onboarding', function () {
    $technician = User::factory()->create([
        'role' => 'technician',
        'account_status' => User::ACCOUNT_REVIEW_PENDING,
    ]);

    $this->post(route('login.store'), [
        'email' => $technician->email,
        'password' => 'password',
    ])->assertSessionHasNoErrors();

    $this->assertAuthenticatedAs($technician);
});

test('google sign in requires configured credentials', function () {
    config()->set('services.google.client_id', null);
    config()->set('services.google.client_secret', null);

    $response = $this->get(route('auth.google.redirect'));

    $response->assertRedirect(route('login'))
        ->assertSessionHasErrors('email');
});

test('existing users can sign in with google', function () {
    $user = User::factory()->create(['email' => 'google@example.com']);

    config()->set('services.google', [
        'client_id' => 'client-id',
        'client_secret' => 'client-secret',
        'redirect' => route('auth.google.callback'),
    ]);

    Http::fake([
        'https://oauth2.googleapis.com/token' => Http::response([
            'access_token' => 'google-access-token',
        ]),
        'https://openidconnect.googleapis.com/v1/userinfo' => Http::response([
            'sub' => 'google-sub-123',
            'email' => 'google@example.com',
            'email_verified' => true,
            'name' => 'Google User',
        ]),
    ]);

    $response = $this->withSession(['google_oauth_state' => 'state-123'])
        ->get(route('auth.google.callback', [
            'code' => 'authorization-code',
            'state' => 'state-123',
        ]));

    $response->assertRedirect(route('dashboard'));
    $this->assertAuthenticatedAs($user);
    expect($user->fresh()->google_id)->toBe('google-sub-123');
    $response->assertSessionHas(
        'google_status',
        'An account with this email already exists in FixTrack. Google has been linked and you have been signed in.',
    );

    $this->get(route('customer.module'))
        ->assertOk()
        ->assertSee('An account with this email already exists in FixTrack.')
        ->assertSee('data-test="google-auth-status"', false);
});

test('already linked google accounts receive a clear sign in message', function () {
    $user = User::factory()->create([
        'email' => 'linked-google@example.com',
        'google_id' => 'linked-google-sub',
    ]);

    config()->set('services.google', [
        'client_id' => 'client-id',
        'client_secret' => 'client-secret',
        'redirect' => route('auth.google.callback'),
    ]);

    Http::fake([
        'https://oauth2.googleapis.com/token' => Http::response([
            'access_token' => 'google-access-token',
        ]),
        'https://openidconnect.googleapis.com/v1/userinfo' => Http::response([
            'sub' => 'linked-google-sub',
            'email' => 'linked-google@example.com',
            'email_verified' => true,
        ]),
    ]);

    $response = $this->withSession(['google_oauth_state' => 'linked-state'])
        ->get(route('auth.google.callback', [
            'code' => 'authorization-code',
            'state' => 'linked-state',
        ]));

    $response->assertRedirect(route('dashboard'))
        ->assertSessionHas(
            'google_status',
            'This Google account is already registered with FixTrack. You have been signed in.',
        );
    $this->assertAuthenticatedAs($user);
});

test('staff cannot use the public google sign in flow', function () {
    User::factory()->create([
        'email' => 'staff-google@example.com',
        'role' => 'staff',
    ]);

    config()->set('services.google', [
        'client_id' => 'client-id',
        'client_secret' => 'client-secret',
        'redirect' => route('auth.google.callback'),
    ]);

    Http::fake([
        'https://oauth2.googleapis.com/token' => Http::response([
            'access_token' => 'google-access-token',
        ]),
        'https://openidconnect.googleapis.com/v1/userinfo' => Http::response([
            'sub' => 'staff-google-sub',
            'email' => 'staff-google@example.com',
            'email_verified' => true,
            'name' => 'Admin User',
        ]),
    ]);

    $response = $this->withSession(['google_oauth_state' => 'staff-state'])
        ->get(route('auth.google.callback', [
            'code' => 'authorization-code',
            'state' => 'staff-state',
        ]));

    $response->assertRedirect(route('login'))
        ->assertSessionHasErrors([
            'email' => 'This email is already registered as a Staff account. Please use the Staff sign-in page.',
        ]);
    $this->assertGuest();
});

test('banned technicians cannot sign in with google or link a google account', function () {
    $technician = User::factory()->create([
        'email' => 'banned-google@example.com',
        'role' => 'technician',
        'account_status' => User::ACCOUNT_BANNED,
    ]);

    config()->set('services.google', [
        'client_id' => 'client-id',
        'client_secret' => 'client-secret',
        'redirect' => route('auth.google.callback'),
    ]);
    Http::fake([
        'https://oauth2.googleapis.com/token' => Http::response(['access_token' => 'google-access-token']),
        'https://openidconnect.googleapis.com/v1/userinfo' => Http::response([
            'sub' => 'banned-google-sub',
            'email' => $technician->email,
            'email_verified' => true,
        ]),
    ]);

    $this->withSession(['google_oauth_state' => 'banned-state'])
        ->get(route('auth.google.callback', ['code' => 'authorization-code', 'state' => 'banned-state']))
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors([
            'email' => 'This FixTrack account has been banned. Contact support if you believe this is a mistake.',
        ]);

    $this->assertGuest();
    expect($technician->fresh()->google_id)->toBeNull();
});

test('suspended technicians receive a clear google sign in message', function () {
    $suspendedUntil = now()->addDays(3)->startOfMinute();
    $technician = User::factory()->create([
        'email' => 'suspended-google@example.com',
        'role' => 'technician',
        'account_status' => 'suspended',
        'suspended_until' => $suspendedUntil,
    ]);

    config()->set('services.google', [
        'client_id' => 'client-id',
        'client_secret' => 'client-secret',
        'redirect' => route('auth.google.callback'),
    ]);
    Http::fake([
        'https://oauth2.googleapis.com/token' => Http::response(['access_token' => 'google-access-token']),
        'https://openidconnect.googleapis.com/v1/userinfo' => Http::response([
            'sub' => 'suspended-google-sub',
            'email' => $technician->email,
            'email_verified' => true,
        ]),
    ]);

    $this->withSession(['google_oauth_state' => 'suspended-state'])
        ->get(route('auth.google.callback', ['code' => 'authorization-code', 'state' => 'suspended-state']))
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors([
            'email' => 'This FixTrack account is suspended until '.$suspendedUntil->format('F j, Y g:i A').'.',
        ]);

    $this->assertGuest();
    expect($technician->fresh()->google_id)->toBeNull();
});

test('google sign in retries a transient token provider failure', function () {
    config()->set('services.google', [
        'client_id' => 'client-id',
        'client_secret' => 'client-secret',
        'redirect' => route('auth.google.callback'),
    ]);
    Http::fake([
        'https://oauth2.googleapis.com/token' => Http::response([], 503),
        'https://openidconnect.googleapis.com/v1/userinfo' => Http::response([], 503),
    ]);

    $response = $this->withSession(['google_oauth_state' => 'state-retry'])
        ->get(route('auth.google.callback', [
            'code' => 'authorization-code',
            'state' => 'state-retry',
        ]));

    $response->assertRedirect(route('login'))
        ->assertSessionHasErrors('email');
    Http::assertSentCount(2);
});

test('google callback rejects an invalid oauth state before contacting google', function () {
    config()->set('services.google', [
        'client_id' => 'client-id',
        'client_secret' => 'client-secret',
        'redirect' => route('auth.google.callback'),
    ]);
    Http::fake();

    $response = $this->withSession(['google_oauth_state' => 'expected-state'])
        ->get(route('auth.google.callback', [
            'code' => 'authorization-code',
            'state' => 'wrong-state',
        ]));

    $response->assertRedirect(route('login'))
        ->assertSessionHasErrors('email');
    Http::assertNothingSent();
});

test('google sign in keeps two factor authentication enabled', function () {
    $this->skipUnlessFortifyHas(Features::twoFactorAuthentication());

    $user = User::factory()->withTwoFactor()->create(['email' => 'google-2fa@example.com']);

    config()->set('services.google', [
        'client_id' => 'client-id',
        'client_secret' => 'client-secret',
        'redirect' => route('auth.google.callback'),
    ]);

    Http::fake([
        'https://oauth2.googleapis.com/token' => Http::response(['access_token' => 'google-access-token']),
        'https://openidconnect.googleapis.com/v1/userinfo' => Http::response([
            'sub' => 'google-sub-2fa',
            'email' => 'google-2fa@example.com',
            'email_verified' => true,
        ]),
    ]);

    $response = $this->withSession(['google_oauth_state' => 'state-2fa'])
        ->get(route('auth.google.callback', [
            'code' => 'authorization-code',
            'state' => 'state-2fa',
        ]));

    $response->assertRedirect(route('two-factor.login'));
    $this->assertGuest();
    expect(session('login.id'))->toBe($user->id);
});

test('successful JSON login response includes the success state signal', function () {
    $user = User::factory()->create();

    $response = $this->withHeader('Accept', 'application/json')->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response->assertOk()->assertJson(['two_factor' => false]);
    $this->assertAuthenticated();
});

test('users can not authenticate with invalid password', function () {
    $user = User::factory()->create();

    $response = $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $response->assertSessionHasErrorsIn('email');

    $this->assertGuest();
});

test('users with two factor enabled are redirected to two factor challenge', function () {
    $this->skipUnlessFortifyHas(Features::twoFactorAuthentication());

    Features::twoFactorAuthentication([
        'confirm' => true,
        'confirmPassword' => true,
    ]);

    $user = User::factory()->withTwoFactor()->create();

    $response = $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response->assertRedirect(route('two-factor.login'));
    $this->assertGuest();
});

test('users can logout', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('logout'));

    $response->assertRedirect(route('home'));

    $this->assertGuest();
});

test('technician logout marks the technician offline', function () {
    $technician = User::factory()->create([
        'role' => 'technician',
        'availability_status' => 'available',
    ]);

    $this->actingAs($technician)->post(route('logout'));

    expect($technician->fresh()->availability_status)->toBe('offline');
});
