<?php

namespace App\Providers;

use App\Models\Booking;
use App\Models\Payment;
use App\Models\SupportTicket;
use App\Models\TechnicianVerification;
use App\Models\WalkInEntry;
use Carbon\CarbonImmutable;
use Illuminate\Database\Connection;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View as ViewInstance;

class AppServiceProvider extends ServiceProvider
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
        $this->configureDefaults();
        $this->configureOperationsNavigation();

        DB::whenQueryingForLongerThan(500, function (Connection $connection, QueryExecuted $event): void {
            Log::warning('Slow database request detected.', [
                'connection' => $connection->getName(),
                'cumulative_threshold_ms' => 500,
                'last_query_ms' => $event->time,
                'route' => app()->bound('request') ? request()->route()?->getName() : null,
            ]);
        });
    }

    private function configureOperationsNavigation(): void
    {
        View::composer('components.app-sidebar-navigation', function (ViewInstance $view): void {
            $user = auth()->user();
            $badges = [];

            if ($user?->isStaff()) {
                $attribute = 'fixtrack.staff_navigation_badges';

                if (! request()->attributes->has($attribute)) {
                    request()->attributes->set($attribute, [
                        'technician-verification' => TechnicianVerification::query()->whereIn('status', ['submitted', 'under_review'])->count(),
                        'service-bookings' => Booking::query()->whereIn('status', ['pending', 'matching'])->count(),
                        'dispatch-monitor' => Booking::query()->whereNull('assigned_technician_id')->whereIn('status', ['pending', 'matching'])->count(),
                        'walk-in-queue' => WalkInEntry::query()->whereIn('status', ['waiting', 'called', 'on_hold'])->count(),
                        'payments-revenue' => Payment::query()->where('status', 'pending')->count(),
                        'support-disputes' => SupportTicket::query()->whereIn('status', ['open', 'in_progress'])->count(),
                    ]);
                }

                $badges = request()->attributes->get($attribute, []);
            }

            $view->with('staffNavigationBadges', $badges);
        });
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
