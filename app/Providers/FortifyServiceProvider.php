<?php

namespace App\Providers;

use App\Actions\ExpireTechnicianSuspension;
use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Models\ServiceCatalog;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Fortify\Fortify;
use Laravel\Passkeys\Contracts\PasskeyUser;
use Laravel\Passkeys\Passkeys;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureActions();
        $this->configureAuthentication();
        $this->configureViews();
        $this->configureRateLimiting();
    }

    /**
     * Configure Fortify actions.
     */
    private function configureActions(): void
    {
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);
        Fortify::createUsersUsing(CreateNewUser::class);
    }

    /**
     * Keep staff and administrator authentication isolated from public account sign-in.
     */
    private function configureAuthentication(): void
    {
        Fortify::authenticateUsing(function (Request $request): ?User {
            $credentials = $request->only(Fortify::username(), 'password');
            $provider = Auth::guard((string) config('fortify.guard'))->getProvider();
            $user = $provider->retrieveByCredentials($credentials);

            if (! $user instanceof User || blank($user->password) || ! $provider->validateCredentials($user, $credentials)) {
                return null;
            }

            if ($user->hasExpiredSuspension()) {
                app(ExpireTechnicianSuspension::class)->restoreIfExpired($user);
            }

            $mayUsePortal = match (true) {
                $request->routeIs('admin.login.store') => $user->isSuperAdmin() && $user->hasActiveAccount(),
                $request->routeIs('staff.login.store') => $user->isStaff() && $user->hasActiveAccount(),
                default => $user->mayUsePublicLogin(),
            };

            if (! $mayUsePortal) {
                return null;
            }

            if (config('hashing.rehash_on_login', true)) {
                $provider->rehashPasswordIfRequired($user, $credentials);
            }

            return $user;
        });

        Passkeys::authorizeLoginUsing(
            function (Request $_request, PasskeyUser $user): bool {
                if (! $user instanceof User) {
                    return false;
                }

                if ($user->hasExpiredSuspension()) {
                    app(ExpireTechnicianSuspension::class)->restoreIfExpired($user);
                }

                return $user->mayUsePublicLogin();
            },
        );
    }

    /**
     * Configure Fortify views.
     */
    private function configureViews(): void
    {
        Fortify::loginView(fn () => view('pages::auth.login'));
        Fortify::verifyEmailView(fn () => view('pages::auth.verify-email'));
        Fortify::twoFactorChallengeView(fn () => view('pages::auth.two-factor-challenge'));
        Fortify::confirmPasswordView(fn () => view('pages::auth.confirm-password'));
        Fortify::registerView(fn () => view('pages::auth.register', ['serviceCatalog' => ServiceCatalog::activeCatalog()]));
        Fortify::resetPasswordView(fn () => view('pages::auth.reset-password'));
        Fortify::requestPasswordResetLinkView(fn () => view('pages::auth.forgot-password'));
    }

    /**
     * Configure rate limiting.
     */
    private function configureRateLimiting(): void
    {
        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });

        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower($request->input(Fortify::username())).'|'.$request->ip());

            return Limit::perMinute(5)->by($throttleKey);
        });

        RateLimiter::for('passkeys', function (Request $request) {
            $credentialId = $request->input('credential.id');

            return Limit::perMinute(10)->by(
                ($credentialId ?: $request->session()->getId()).'|'.$request->ip(),
            );
        });

        RateLimiter::for('realtime', function (Request $request) {
            return Limit::perMinute(240)->by(
                ($request->user()?->getAuthIdentifier() ?? 'guest').'|'.$request->ip(),
            );
        });
    }
}
