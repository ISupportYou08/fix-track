<?php

namespace App\Livewire\SuperAdmin;

use App\Models\Booking;
use App\Models\ServiceCatalog;
use App\Models\SupportTicket;
use App\Models\TechnicianVerification;
use App\Models\User;
use App\Models\WalkInEntry;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Number;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Operations Dashboard')]
class Dashboard extends Component
{
    /** @var array<string, bool> */
    private array $tableAvailability = [];

    public function mount(): void
    {
        abort_unless(auth()->user()?->canAccessOperationsWorkspace(), 403);
    }

    public function render(): View
    {
        return view('livewire.super-admin.dashboard', [
            'firstName' => Str::before(auth()->user()->name, ' '),
            'isAdministrator' => auth()->user()->isSuperAdmin(),
            'userOverview' => $this->userOverview(),
            'staffOverview' => $this->staffOverview(),
            'bookingTrend' => $this->bookingTrend(),
            'statusBreakdown' => $this->statusBreakdown(),
            'recentBookings' => $this->recentBookings(),
            'topServices' => $this->topServices(),
            'needsAttention' => $this->needsAttention(),
            'systemStatus' => $this->systemStatus(),
        ]);
    }

    /** @return array{pendingBookings: int, unassignedJobs: int, waitingWalkIns: int, openTickets: int, technicianReviews: int} */
    private function staffOverview(): array
    {
        if (! auth()->user()->isStaff()) {
            return ['pendingBookings' => 0, 'unassignedJobs' => 0, 'waitingWalkIns' => 0, 'openTickets' => 0, 'technicianReviews' => 0];
        }

        return [
            'pendingBookings' => $this->tableExists('bookings')
                ? Booking::query()->whereIn('status', ['pending', 'matching'])->count()
                : 0,
            'unassignedJobs' => $this->tableExists('bookings')
                ? Booking::query()->whereNull('assigned_technician_id')->whereIn('status', ['pending', 'matching'])->count()
                : 0,
            'waitingWalkIns' => $this->tableExists('walk_in_entries')
                ? WalkInEntry::query()->whereIn('status', ['waiting', 'called', 'on_hold'])->count()
                : 0,
            'openTickets' => $this->tableExists('support_tickets')
                ? SupportTicket::query()->whereIn('status', ['open', 'in_progress'])->count()
                : 0,
            'technicianReviews' => $this->tableExists('technician_verifications')
                ? TechnicianVerification::query()->whereIn('status', ['submitted', 'under_review'])->count()
                : 0,
        ];
    }

    /** @return array{total: int, customers: int, technicians: int, staff: int, restricted: int} */
    private function userOverview(): array
    {
        if (! auth()->user()->isSuperAdmin() || ! $this->tableExists('users')) {
            return ['total' => 0, 'customers' => 0, 'technicians' => 0, 'staff' => 0, 'restricted' => 0];
        }

        return [
            'total' => User::query()->count(),
            'customers' => User::query()->where('role', 'customer')->count(),
            'technicians' => User::query()->where('role', 'technician')->count(),
            'staff' => User::query()->where('role', 'staff')->count(),
            'restricted' => User::query()->whereIn('account_status', ['suspended', User::ACCOUNT_BANNED])->count(),
        ];
    }

    /**
     * @return array{
     *     days: array<int, array{date: string, label: string, active: int, completed: int, cancelled: int, x: int, activeY: int, completedY: int, cancelledY: int}>,
     *     paths: array{active: string, completed: string, cancelled: string},
     *     area: string,
     *     maxValue: int,
     *     summary: array{total: int, peakLabel: string, peakValue: int},
     * }
     */
    private function bookingTrend(): array
    {
        $trend = [];

        foreach (range(6, 0) as $daysAgo) {
            $date = now()->subDays($daysAgo)->startOfDay();
            $trend[$date->toDateString()] = [
                'date' => $date->toDateString(),
                'label' => $date->format('D'),
                'active' => 0,
                'completed' => 0,
                'cancelled' => 0,
            ];
        }

        if ($this->tableExists('bookings')) {
            $rows = Booking::query()
                ->selectRaw('DATE(created_at) as day, status, COUNT(*) as total')
                ->where('created_at', '>=', now()->subDays(6)->startOfDay())
                ->groupByRaw('DATE(created_at), status')
                ->get();

            foreach ($rows as $row) {
                $day = (string) $row->day;

                if (! isset($trend[$day])) {
                    continue;
                }

                $bucket = match ((string) $row->status) {
                    'completed' => 'completed',
                    'cancelled', 'no_show' => 'cancelled',
                    'pending', 'matching', 'assigned', 'en_route', 'in_progress' => 'active',
                    default => null,
                };

                if ($bucket !== null) {
                    $trend[$day][$bucket] += (int) $row->total;
                }
            }
        }

        $maxValue = max(2, ...array_map(
            fn (array $day): int => max($day['active'], $day['completed'], $day['cancelled']),
            array_values($trend),
        ));

        $days = array_values($trend);

        foreach ($days as $index => &$day) {
            $day['x'] = 44 + $index * 102;
            $day['activeY'] = 180 - (int) round($day['active'] / $maxValue * 144);
            $day['completedY'] = 180 - (int) round($day['completed'] / $maxValue * 144);
            $day['cancelledY'] = 180 - (int) round($day['cancelled'] / $maxValue * 144);
        }
        unset($day);

        $paths = [];

        foreach (['active', 'completed', 'cancelled'] as $key) {
            $paths[$key] = $this->smoothChartPath($days, $key);
        }

        $total = 0;
        $peakLabel = '—';
        $peakValue = 0;

        foreach ($days as $day) {
            $dayTotal = $day['active'] + $day['completed'] + $day['cancelled'];
            $total += $dayTotal;

            if ($dayTotal > $peakValue) {
                $peakLabel = $day['label'];
                $peakValue = $dayTotal;
            }
        }

        return [
            'days' => $days,
            'paths' => [
                'active' => $paths['active'],
                'completed' => $paths['completed'],
                'cancelled' => $paths['cancelled'],
            ],
            'area' => $this->chartAreaPath($days, 'active'),
            'maxValue' => $maxValue,
            'summary' => [
                'total' => $total,
                'peakLabel' => $peakLabel,
                'peakValue' => $peakValue,
            ],
        ];
    }

    /**
     * @param  array<int, array{date: string, label: string, active: int, completed: int, cancelled: int, x: int, activeY: int, completedY: int, cancelledY: int}>  $days
     */
    private function smoothChartPath(array $days, string $series): string
    {
        $firstDay = $days[0];
        $path = 'M '.$firstDay['x'].' '.$firstDay[$series.'Y'];

        for ($index = 1, $count = count($days); $index < $count; $index++) {
            $previousDay = $days[$index - 1];
            $day = $days[$index];
            $middleX = (int) round(($previousDay['x'] + $day['x']) / 2);

            $path .= ' C '.$middleX.' '.$previousDay[$series.'Y'].', '.$middleX.' '.$day[$series.'Y'].', '.$day['x'].' '.$day[$series.'Y'];
        }

        return $path;
    }

    /**
     * @param  array<int, array{date: string, label: string, active: int, completed: int, cancelled: int, x: int, activeY: int, completedY: int, cancelledY: int}>  $days
     */
    private function chartAreaPath(array $days, string $series): string
    {
        $lastDay = $days[count($days) - 1];

        return $this->smoothChartPath($days, $series)
            .' L '.$lastDay['x'].' 180 L '.$days[0]['x'].' 180 Z';
    }

    /** @return array<int, array{key: string, label: string, value: int, percentage: int, color: string}> */
    private function statusBreakdown(): array
    {
        $counts = collect();

        if ($this->tableExists('bookings')) {
            $counts = Booking::query()
                ->select('status', DB::raw('COUNT(*) as total'))
                ->groupBy('status')
                ->pluck('total', 'status')
                ->map(fn (mixed $total): int => (int) $total);
        }

        $statuses = [
            ['key' => 'waiting', 'label' => 'Waiting', 'statuses' => ['pending', 'matching'], 'color' => 'violet'],
            ['key' => 'active', 'label' => 'Active', 'statuses' => ['assigned', 'en_route', 'in_progress'], 'color' => 'blue'],
            ['key' => 'completed', 'label' => 'Completed', 'statuses' => ['completed'], 'color' => 'emerald'],
            ['key' => 'cancelled', 'label' => 'Cancelled / no-show', 'statuses' => ['cancelled', 'no_show'], 'color' => 'rose'],
        ];
        $total = max(1, (int) collect($statuses)->sum(
            fn (array $status): int => (int) $counts->only($status['statuses'])->sum(),
        ));

        return array_map(function (array $status) use ($counts, $total): array {
            $value = (int) $counts->only($status['statuses'])->sum();

            return [
                'key' => $status['key'],
                'label' => $status['label'],
                'value' => $value,
                'percentage' => (int) round($value / $total * 100),
                'color' => $status['color'],
            ];
        }, $statuses);
    }

    /** @return array<int, array<string, mixed>> */
    private function recentBookings(): array
    {
        if (! $this->tableExists('bookings')) {
            return [];
        }

        return Booking::query()
            ->with('customer:id,name')
            ->select(['id', 'user_id', 'reference', 'customer_name', 'service_type', 'status', 'is_priority', 'created_at'])
            ->latest('created_at')
            ->limit(6)
            ->get()
            ->map(fn (Booking $booking): array => [
                'id' => $booking->id,
                'reference' => (string) $booking->reference,
                'href' => route($this->operationsRouteName('module'), ['module' => 'service-bookings', 'booking' => $booking->id]),
                'customer' => $booking->customer_name ?: (string) data_get($booking->customer, 'name', '—'),
                'service' => $this->serviceLabel($booking->service_type),
                'status' => $this->statusLabel((string) $booking->status),
                'statusColor' => $this->statusColor((string) $booking->status),
                'priority' => (bool) $booking->is_priority,
                'createdAt' => $booking->created_at ? Carbon::parse($booking->created_at)->diffForHumans() : '—',
            ])
            ->all();
    }

    /** @return array<int, array{code: string, label: string, value: int, percentage: int}> */
    private function topServices(): array
    {
        if (! $this->tableExists('bookings')) {
            return [];
        }

        $services = Booking::query()
            ->select('service_type', DB::raw('COUNT(*) as total'))
            ->groupBy('service_type')
            ->orderByDesc('total')
            ->limit(5)
            ->get();
        $maxValue = max(1, (int) ($services->max('total') ?? 0));

        return $services->map(fn (Booking $service): array => [
            'code' => (string) $service->service_type,
            'label' => $this->serviceLabel($service->service_type),
            'value' => (int) $service->total,
            'percentage' => (int) round((int) $service->total / $maxValue * 100),
        ])->all();
    }

    /** @return array<int, array{key: string, title: string, detail: string, status: string, color: string, href: string}> */
    private function needsAttention(): array
    {
        $items = [];

        if ($this->tableExists('bookings')) {
            $items = Booking::query()
                ->with('customer:id,name')
                ->select(['id', 'user_id', 'reference', 'customer_name', 'service_type', 'status', 'created_at'])
                ->whereIn('status', ['pending', 'matching'])
                ->oldest('created_at')
                ->limit(4)
                ->get()
                ->map(fn (Booking $booking): array => [
                    'key' => 'booking-'.$booking->id,
                    'title' => (string) $booking->reference,
                    'detail' => $this->serviceLabel($booking->service_type).' · '.($booking->customer_name ?: (string) data_get($booking->customer, 'name', '—')).' · waiting '.$booking->created_at?->diffForHumans(short: true),
                    'status' => $booking->created_at?->lt(now()->subMinutes(15)) ? 'Dispatch overdue' : $this->statusLabel((string) $booking->status),
                    'color' => $booking->created_at?->lt(now()->subMinutes(15)) ? 'red' : $this->statusColor((string) $booking->status),
                    'href' => route($this->operationsRouteName('module'), ['module' => 'service-bookings', 'booking' => $booking->id]),
                ])
                ->all();
        }

        if ($this->tableExists('technician_verifications')) {
            $verificationItems = TechnicianVerification::query()
                ->with('technician:id,name')
                ->select(['id', 'user_id', 'status'])
                ->whereIn('technician_verifications.status', ['submitted', 'under_review'])
                ->oldest('submitted_at')
                ->limit(2)
                ->get()
                ->map(fn (TechnicianVerification $verification): array => [
                    'key' => 'verification-'.$verification->id,
                    'title' => 'Technician verification',
                    'detail' => (string) data_get($verification->technician, 'name', '—'),
                    'status' => 'Review',
                    'color' => 'violet',
                    'href' => route($this->operationsRouteName('module'), ['module' => 'technician-verification']),
                ])
                ->all();

            $items = [...$items, ...$verificationItems];
        }

        return array_slice($items, 0, 5);
    }

    /** @return array<int, array{label: string, value: string, detail: string, color: string}> */
    private function systemStatus(): array
    {
        $queueCount = $this->tableExists('jobs') ? DB::table('jobs')->count() : 0;
        $failedCount = $this->tableExists('failed_jobs') ? DB::table('failed_jobs')->count() : 0;
        $missingTables = array_values(array_filter(
            ['users', 'bookings', 'service_catalog', 'technician_verifications', 'payments'],
            fn (string $table): bool => ! $this->tableExists($table),
        ));
        $schemaReady = $missingTables === [];

        return [
            [
                'label' => 'Application',
                'value' => 'Online',
                'detail' => 'Laravel '.app()->version(),
                'color' => 'emerald',
            ],
            [
                'label' => 'Operations schema',
                'value' => $schemaReady ? 'Ready' : 'Pending',
                'detail' => $schemaReady ? 'Core records connected' : Number::format(count($missingTables)).' required tables missing',
                'color' => $schemaReady ? 'emerald' : 'amber',
            ],
            [
                'label' => 'Queue pipeline',
                'value' => $failedCount > 0 ? 'Attention' : 'Healthy',
                'detail' => Number::format($queueCount).' pending · '.Number::format($failedCount).' failed',
                'color' => $failedCount > 0 ? 'amber' : 'emerald',
            ],
        ];
    }

    private function tableExists(string $table): bool
    {
        return $this->tableAvailability[$table] ??= Schema::hasTable($table);
    }

    private function statusLabel(string $status): string
    {
        return Str::headline($status);
    }

    private function serviceLabel(?string $service): string
    {
        $code = (string) $service;

        if (! $this->tableExists('service_catalog')) {
            return Str::headline($code);
        }

        $labels = once(fn (): array => ServiceCatalog::query()->pluck('name', 'code')->all());

        return (string) ($labels[$code] ?? Str::headline($code));
    }

    private function statusColor(string $status): string
    {
        return match ($status) {
            'completed' => 'emerald',
            'cancelled' => 'rose',
            'assigned', 'en_route', 'in_progress' => 'sky',
            'matching' => 'violet',
            default => 'amber',
        };
    }

    private function operationsRouteName(string $route): string
    {
        $user = auth()->user();

        abort_unless($user instanceof User, 403);

        return $user->operationsRouteName($route);
    }
}
