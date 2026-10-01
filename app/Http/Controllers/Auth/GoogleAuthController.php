<?php

namespace App\Http\Controllers\Auth;

use App\Actions\ExpireTechnicianSuspension;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Fortify\Events\TwoFactorAuthenticationChallenged;
use Laravel\Fortify\Fortify;
use Throwable;

class GoogleAuthController extends Controller
{
    public function redirect(Request $request): RedirectResponse
    {
        if ($this->isNotConfigured()) {
            return redirect()->route('login')->withErrors([
                'email' => __('Google sign-in is not configured yet.'),
            ]);
        }

        $state = Str::random(64);

        $request->session()->put('google_oauth_state', $state);

        $query = http_build_query([
            'client_id' => config('services.google.client_id'),
            'redirect_uri' => config('services.google.redirect'),
            'response_type' => 'code',
            'scope' => 'openid email profile',
            'state' => $state,
            'prompt' => 'select_account',
        ]);

        return redirect()->away('https://accounts.google.com/o/oauth2/v2/auth?'.$query);
    }

    public function callback(Request $request): RedirectResponse
    {
        if ($request->filled('error')) {
            $request->session()->forget('google_oauth_state');

            return redirect()->route('login')->withErrors([
                'email' => __('Google sign-in was cancelled.'),
            ]);
        }

        $expectedState = (string) $request->session()->pull('google_oauth_state');
        $receivedState = (string) $request->query('state');

        if ($expectedState === '' || $receivedState === '' || ! hash_equals($expectedState, $receivedState)) {
            return redirect()->route('login')->withErrors([
                'email' => __('Google sign-in expired. Please try again.'),
            ]);
        }

        if (! $request->filled('code') || $this->isNotConfigured()) {
            return redirect()->route('login')->withErrors([
                'email' => __('Google sign-in could not be completed.'),
            ]);
        }

        try {
            $tokenResponse = Http::asForm()
                ->connectTimeout(3)
                ->timeout(10)
                ->retry(2, 200, throw: false)
                ->post('https://oauth2.googleapis.com/token', [
                    'code' => $request->string('code')->toString(),
                    'client_id' => config('services.google.client_id'),
                    'client_secret' => config('services.google.client_secret'),
                    'redirect_uri' => config('services.google.redirect'),
                    'grant_type' => 'authorization_code',
                ]);

            $tokenResponse->throw();

            $accessToken = $tokenResponse->json('access_token');

            if (! is_string($accessToken) || $accessToken === '') {
                throw new \RuntimeException('Google did not return an access token.');
            }

            $profileResponse = Http::withToken($accessToken)
                ->connectTimeout(3)
                ->timeout(10)
                ->retry(2, 200, throw: false)
                ->get('https://openidconnect.googleapis.com/v1/userinfo');

            $profileResponse->throw();

            $profile = $profileResponse->json();
            $googleId = (string) data_get($profile, 'sub');
            $email = Str::lower((string) data_get($profile, 'email'));
            $emailVerified = filter_var(data_get($profile, 'email_verified'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

            if ($googleId === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL) || $emailVerified !== true) {
                throw new \RuntimeException('Google returned an incomplete or unverified profile.');
            }

            $user = User::query()->where('google_id', $googleId)->first();
            $matchedExistingEmail = false;

            if (! $user) {
                $user = User::query()->where('email', $email)->first();
                $matchedExistingEmail = $user !== null;
            }

            if (! $user) {
                $request->session()->put('google_registration', [
                    'google_id' => $googleId,
                    'email' => $email,
                    'name' => Str::of((string) data_get($profile, 'name'))->squish()->limit(255)->toString(),
                    'first_name' => Str::of((string) data_get($profile, 'given_name'))->squish()->limit(255)->toString(),
                    'surname' => Str::of((string) data_get($profile, 'family_name'))->squish()->limit(255)->toString(),
                    'picture' => filter_var(data_get($profile, 'picture'), FILTER_VALIDATE_URL) ?: null,
                    'expires_at' => now()->addMinutes(15)->getTimestamp(),
                ]);

                return redirect()->route('auth.google.register.show');
            }

            if ($user->hasExpiredSuspension()) {
                app(ExpireTechnicianSuspension::class)->restoreIfExpired($user);
                $user->refresh();
            }

            if (! $user->mayUsePublicLogin()) {
                return redirect()->route('login')->withErrors([
                    'email' => $this->unavailableAccountMessage($user),
                ]);
            }

            if ($user->google_id !== null && ! hash_equals((string) $user->google_id, $googleId)) {
                return redirect()->route('login')->withErrors([
                    'email' => __('This Google account is linked to a different FixTrack account.'),
                ]);
            }

            $user->forceFill([
                'google_id' => $googleId,
                'email_verified_at' => $user->email_verified_at ?? now(),
            ])->save();

            if (Fortify::confirmsTwoFactorAuthentication() && $user->two_factor_secret && $user->two_factor_confirmed_at) {
                $request->session()->put([
                    'login.id' => $user->getKey(),
                    'login.remember' => true,
                ]);

                TwoFactorAuthenticationChallenged::dispatch($user);

                return redirect()->route('two-factor.login');
            }

            Auth::login($user, remember: true);
            $request->session()->regenerate();
            $request->session()->flash(
                'google_status',
                $matchedExistingEmail
                    ? __('An account with this email already exists in FixTrack. Google has been linked and you have been signed in.')
                    : __('This Google account is already registered with FixTrack. You have been signed in.'),
            );

            return redirect()->intended(route('dashboard'));
        } catch (Throwable $exception) {
            Log::warning('Google sign-in failed.', [
                'message' => $exception->getMessage(),
            ]);

            return redirect()->route('login')->withErrors([
                'email' => __('Google sign-in could not be completed. Please try again.'),
            ]);
        }
    }

    private function isNotConfigured(): bool
    {
        return blank(config('services.google.client_id'))
            || blank(config('services.google.client_secret'))
            || blank(config('services.google.redirect'));
    }

    private function unavailableAccountMessage(User $user): string
    {
        if ($user->account_status === User::ACCOUNT_BANNED) {
            return __('This FixTrack account has been banned. Contact support if you believe this is a mistake.');
        }

        if ($user->account_status === 'suspended') {
            if ($user->suspended_until !== null) {
                return __('This FixTrack account is suspended until :date.', [
                    'date' => $user->suspended_until->timezone(config('app.timezone'))->format('F j, Y g:i A'),
                ]);
            }

            return __('This FixTrack account is currently suspended. Contact support for assistance.');
        }

        if ($user->isStaff()) {
            return __('This email is already registered as a Staff account. Please use the Staff sign-in page.');
        }

        if ($user->isSuperAdmin()) {
            return __('This email is already registered as an Administrator account. Please use the Administrator sign-in page.');
        }

        return __('This email is already registered, but the account cannot use public Google sign-in.');
    }
}
