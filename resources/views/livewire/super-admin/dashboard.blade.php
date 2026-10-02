<div
    class="admin-dashboard-reference mx-auto flex min-w-0 w-full max-w-7xl flex-col gap-5 px-4 pt-4 font-sans text-gray-800 sm:gap-6 sm:px-6 sm:pt-6 lg:px-0 lg:pt-0 dark:text-zinc-100"
    data-app-responsive-page
    data-realtime-scope="admin-dashboard"
    data-realtime-url="{{ route('realtime.snapshot', ['scope' => 'admin-dashboard']) }}"
    data-realtime-interval="10000"
>
    <header class="dashboard-reveal mb-2">
        <flux:breadcrumbs>
            <flux:breadcrumbs.item :href="route(auth()->user()->operationsRouteName('dashboard'))" wire:navigate>{{ auth()->user()->isStaff() ? __('Staff') : __('Administrator') }}</flux:breadcrumbs.item>
            <flux:breadcrumbs.item>Dashboard</flux:breadcrumbs.item>
        </flux:breadcrumbs>

        <div class="mt-3 flex flex-wrap items-center justify-between gap-x-6 gap-y-4" data-app-page-header>
            <div class="min-w-0">
                <h1 class="flex items-center gap-2 text-2xl font-bold tracking-tight text-gray-900 dark:text-white">{{ $isAdministrator ? 'Administrator Control Center' : 'Staff Operations Dashboard' }}</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-zinc-400">{{ $isAdministrator ? 'Manage people, platform access, and service operations from one workspace.' : 'Handle technician reviews, bookings, dispatch, payments, and customer requests.' }}</p>
            </div>

            <div class="flex flex-wrap items-center gap-2" data-app-page-actions>
                @if ($isAdministrator)
                    <flux:button :href="route('admin.module', ['module' => 'users-roles'])" wire:navigate variant="primary" icon="users">Manage all users</flux:button>
                    <flux:button :href="route('admin.module', ['module' => 'platform-settings'])" wire:navigate variant="outline" icon="cog-6-tooth">Platform settings</flux:button>
                @endif
                <span class="inline-flex items-center gap-2 rounded-full border border-emerald-100 bg-emerald-50 px-3 py-1.5 text-xs font-medium text-emerald-700 dark:border-emerald-500/20 dark:bg-emerald-500/10 dark:text-emerald-300"><span class="size-1.5 rounded-full bg-emerald-500" aria-hidden="true"></span>Live overview</span>
            </div>
        </div>
    </header>

    @if ($isAdministrator)
        <section class="dashboard-reveal overflow-hidden rounded-2xl bg-gradient-to-br from-slate-950 via-blue-950 to-indigo-950 p-6 text-white shadow-xl lg:p-8" data-admin-access-overview>
            <div class="flex flex-wrap items-start justify-between gap-5">
                <div>
                    <div class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-3 py-1 text-xs font-semibold uppercase tracking-wider text-blue-100">
                        <flux:icon name="shield-check" class="size-4" /> Full platform access
                    </div>
                    <h2 class="mt-4 text-xl font-semibold">Account and access overview</h2>
                    <p class="mt-1 max-w-2xl text-sm text-blue-100/75">Review every customer, technician, staff member, and administrator. Restricted accounts stay visible for follow-up.</p>
                </div>
                <a href="{{ route('admin.module', ['module' => 'users-roles']) }}" wire:navigate class="inline-flex items-center gap-2 rounded-xl bg-white px-4 py-2.5 text-sm font-semibold text-slate-950 shadow-sm transition hover:bg-blue-50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white">
                    Open user directory <flux:icon name="arrow-right" class="size-4" />
                </a>
            </div>

            <div class="mt-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
                @foreach ([
                    ['label' => 'All users', 'value' => $userOverview['total'], 'icon' => 'users'],
                    ['label' => 'Customers', 'value' => $userOverview['customers'], 'icon' => 'user'],
                    ['label' => 'Technicians', 'value' => $userOverview['technicians'], 'icon' => 'wrench-screwdriver'],
                    ['label' => 'Staff', 'value' => $userOverview['staff'], 'icon' => 'identification'],
                    ['label' => 'Restricted', 'value' => $userOverview['restricted'], 'icon' => 'no-symbol'],
                ] as $accountStat)
                    <div class="rounded-xl border border-white/10 bg-white/[0.08] p-4 backdrop-blur-sm">
                        <div class="flex items-center justify-between gap-3 text-blue-100/70">
                            <span class="text-xs font-medium uppercase tracking-wider">{{ $accountStat['label'] }}</span>
                            <flux:icon :name="$accountStat['icon']" class="size-4" />
                        </div>
                        <div class="mt-2 text-2xl font-semibold tabular-nums">{{ number_format($accountStat['value']) }}</div>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    @if (! $isAdministrator)
        <section class="dashboard-reveal rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-zinc-900 lg:p-6" data-staff-operations-summary>
            <div class="flex flex-wrap items-end justify-between gap-3">
                <div>
                    <h2 class="text-xl font-bold tracking-tight text-gray-900 dark:text-white">Today’s operations</h2>
                    <p class="mt-1 text-base text-gray-500 dark:text-zinc-400">Open work that may need your attention.</p>
                </div>
                <span class="rounded-full bg-blue-50 px-3 py-1.5 text-sm font-semibold text-blue-700 dark:bg-blue-500/10 dark:text-blue-300">Live workload</span>
            </div>

            <div class="mt-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
                @foreach ([
                    ['label' => 'Pending bookings', 'value' => $staffOverview['pendingBookings'], 'icon' => 'clipboard-document-list', 'module' => 'service-bookings', 'tone' => 'blue'],
                    ['label' => 'Unassigned jobs', 'value' => $staffOverview['unassignedJobs'], 'icon' => 'map', 'module' => 'dispatch-monitor', 'tone' => 'violet'],
                    ['label' => 'Waiting walk-ins', 'value' => $staffOverview['waitingWalkIns'], 'icon' => 'queue-list', 'module' => 'walk-in-queue', 'tone' => 'amber'],
                    ['label' => 'Open support tickets', 'value' => $staffOverview['openTickets'], 'icon' => 'chat-bubble-left-right', 'module' => 'support-disputes', 'tone' => 'rose'],
                    ['label' => 'Technician reviews', 'value' => $staffOverview['technicianReviews'], 'icon' => 'identification', 'module' => 'technician-verification', 'tone' => 'emerald'],
                ] as $staffStat)
                    @php
                        $staffIconClass = match ($staffStat['tone']) {
                            'blue' => 'text-blue-500',
                            'violet' => 'text-violet-500',
                            'amber' => 'text-amber-500',
                            'rose' => 'text-rose-500',
                            default => 'text-emerald-500',
                        };
                    @endphp
                    <a href="{{ route('staff.module', ['module' => $staffStat['module']]) }}" wire:navigate class="group rounded-xl border border-zinc-200 p-4 transition hover:-translate-y-0.5 hover:border-blue-300 hover:shadow-md focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-500 dark:border-white/10 dark:hover:border-blue-500/40">
                        <div class="flex items-center justify-between gap-3">
                            <span class="text-base font-semibold text-gray-700 dark:text-zinc-200">{{ $staffStat['label'] }}</span>
                            <flux:icon :name="$staffStat['icon']" class="size-5 {{ $staffIconClass }}" />
                        </div>
                        <div class="mt-3 text-3xl font-bold tabular-nums text-gray-950 dark:text-white">{{ number_format($staffStat['value']) }}</div>
                        <div class="mt-1 text-sm font-medium text-blue-600 opacity-0 transition group-hover:opacity-100 dark:text-blue-300">Open queue →</div>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    <div class="dashboard-stagger grid gap-6 lg:grid-cols-3">
        <flux:card class="dashboard-reveal card-lift flex flex-col !rounded-2xl !border-gray-100 !bg-white !p-5 shadow-sm lg:col-span-2 dark:!border-zinc-700 dark:!bg-zinc-900" data-super-admin-booking-activity-layout="reference">
            <div class="-mx-5 -mt-5 flex flex-wrap items-center justify-between gap-3 border-b border-gray-100 px-5 py-4 dark:border-zinc-800">
                <div>
                    <h2 class="text-lg font-bold tracking-tight text-gray-900 dark:text-white">Booking activity</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-zinc-400">Seven-day view of active, completed, and cancelled jobs.</p>
                </div>

                <div class="flex items-center gap-2">
                    <div class="flex flex-wrap gap-3 text-xs text-zinc-500 dark:text-zinc-400">
                        <span class="inline-flex items-center gap-1.5"><i class="size-2 rounded-full bg-violet-400"></i>Active</span>
                        <span class="inline-flex items-center gap-1.5"><i class="size-2 rounded-full bg-emerald-400"></i>Completed</span>
                        <span class="inline-flex items-center gap-1.5"><i class="size-2 rounded-full bg-rose-400"></i>Cancelled / no-show</span>
                    </div>
                    <x-super-admin.section-menu :actions="[
                        ['label' => __('Refresh section'), 'icon' => 'arrow-path', 'wire' => '$refresh'],
                        ['label' => __('View bookings'), 'icon' => 'arrow-top-right-on-square', 'href' => route(auth()->user()->operationsRouteName('module'), ['module' => 'service-bookings'])],
                    ]" />
                </div>
            </div>

            <div class="mt-5 flex flex-wrap items-end justify-between gap-4" data-super-admin-chart-summary>
                <div>
                    <div class="text-4xl font-bold tracking-tight text-zinc-950 dark:text-white">{{ number_format($bookingTrend['summary']['total']) }}</div>
                    <div class="mt-1 text-sm font-medium text-zinc-500 dark:text-zinc-400">Total bookings in the last 7 days</div>
                </div>
                @if ($bookingTrend['summary']['total'] > 0)
                    <div class="rounded-xl bg-violet-50 px-4 py-2.5 text-start sm:text-end dark:bg-violet-500/10">
                        <div class="text-sm font-bold text-violet-700 dark:text-violet-300">{{ $bookingTrend['summary']['peakLabel'] }}</div>
                        <div class="mt-0.5 text-xs font-medium text-violet-600/80 dark:text-violet-300/80">Peak activity · {{ $bookingTrend['summary']['peakValue'] }} {{ Str::plural('booking', $bookingTrend['summary']['peakValue']) }}</div>
                    </div>
                @endif
            </div>

            @if ($bookingTrend['summary']['total'] === 0)
                <div class="mt-4 flex min-h-[15.625rem] flex-1 flex-col items-center justify-center rounded-xl border border-dashed border-zinc-200 bg-zinc-50/70 px-6 text-center dark:border-white/10 dark:bg-white/[0.03]" data-super-admin-booking-activity-empty>
                    <div class="flex size-14 items-center justify-center rounded-2xl bg-blue-100 text-blue-600 dark:bg-blue-500/15 dark:text-blue-300">
                        <flux:icon name="chart-bar" class="size-7" />
                    </div>
                    <h3 class="mt-4 text-base font-bold text-zinc-900 dark:text-white">No booking activity in the last 7 days</h3>
                    <p class="mt-1 max-w-sm text-sm leading-6 text-zinc-500 dark:text-zinc-400">Start receiving bookings to see daily trends here.</p>
                </div>
            @else
            <div class="relative mt-4 min-h-[15.625rem] flex-1 overflow-x-auto overflow-y-hidden rounded-xl bg-white dark:bg-zinc-900" data-super-admin-booking-activity-chart x-data="{ hoveredDay: null }">
                <div
                    x-cloak
                    x-show="hoveredDay"
                    x-transition
                    class="pointer-events-none absolute end-4 top-4 z-10 w-44 rounded-lg border border-zinc-200 bg-white/95 p-3 shadow-lg dark:border-white/10 dark:bg-zinc-800/95"
                    data-super-admin-booking-tooltip
                >
                    <div class="text-xs font-semibold text-zinc-900 dark:text-white" x-text="hoveredDay ? hoveredDay.label : ''"></div>
                    <div class="mt-2 space-y-1 text-xs">
                        <div class="flex items-center justify-between gap-3"><span class="text-violet-600 dark:text-violet-300">Active</span><span class="font-medium tabular-nums text-zinc-700 dark:text-zinc-200" x-text="hoveredDay ? hoveredDay.active : 0"></span></div>
                        <div class="flex items-center justify-between gap-3"><span class="text-emerald-600 dark:text-emerald-300">Completed</span><span class="font-medium tabular-nums text-zinc-700 dark:text-zinc-200" x-text="hoveredDay ? hoveredDay.completed : 0"></span></div>
                        <div class="flex items-center justify-between gap-3"><span class="text-rose-600 dark:text-rose-300">Cancelled / no-show</span><span class="font-medium tabular-nums text-zinc-700 dark:text-zinc-200" x-text="hoveredDay ? hoveredDay.cancelled : 0"></span></div>
                    </div>
                </div>

                <svg viewBox="0 0 700 220" class="h-full min-h-[12rem] w-full min-w-[34rem] sm:min-w-0" role="img" aria-label="Seven-day booking activity line chart" preserveAspectRatio="none">
                    <defs>
                        <linearGradient id="super-admin-active-area" x1="0" y1="0" x2="0" y2="1">
                            <stop offset="0%" stop-color="#8b5cf6" stop-opacity="0.24" />
                            <stop offset="100%" stop-color="#8b5cf6" stop-opacity="0" />
                        </linearGradient>
                    </defs>

                    <g data-super-admin-chart-grid>
                        <line x1="42" y1="180" x2="660" y2="180" class="stroke-zinc-200 dark:stroke-white/10" />
                        <line x1="42" y1="108" x2="660" y2="108" class="stroke-zinc-200/70 dark:stroke-white/[0.06]" />
                        <line x1="42" y1="36" x2="660" y2="36" class="stroke-zinc-200/70 dark:stroke-white/[0.06]" />
                        <text x="26" y="184" text-anchor="end" class="fill-zinc-600 text-xs dark:fill-zinc-300">0</text>
                        <text x="26" y="112" text-anchor="end" class="fill-zinc-600 text-xs dark:fill-zinc-300" data-super-admin-chart-y-tick="middle" data-value="{{ (int) ceil($bookingTrend['maxValue'] / 2) }}">{{ (int) ceil($bookingTrend['maxValue'] / 2) }}</text>
                        <text x="26" y="40" text-anchor="end" class="fill-zinc-600 text-xs dark:fill-zinc-300" data-super-admin-chart-y-tick="top" data-value="{{ $bookingTrend['maxValue'] }}">{{ $bookingTrend['maxValue'] }}</text>
                    </g>

                    <path data-super-admin-booking-area="active" d="{{ $bookingTrend['area'] }}" fill="url(#super-admin-active-area)" />
                    <path data-super-admin-booking-series="active" data-super-admin-booking-series-type="smooth" d="{{ $bookingTrend['paths']['active'] }}" fill="none" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" class="stroke-violet-400" vector-effect="non-scaling-stroke" />
                    <path data-super-admin-booking-series="completed" data-super-admin-booking-series-type="smooth" d="{{ $bookingTrend['paths']['completed'] }}" fill="none" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" class="stroke-emerald-400" vector-effect="non-scaling-stroke" />
                    <path data-super-admin-booking-series="cancelled" data-super-admin-booking-series-type="smooth" d="{{ $bookingTrend['paths']['cancelled'] }}" fill="none" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" class="stroke-rose-400" vector-effect="non-scaling-stroke" />

                    @foreach ($bookingTrend['days'] as $day)
                        <g
                            wire:key="dashboard-activity-{{ $day['date'] }}"
                            role="button"
                            tabindex="0"
                            aria-label="{{ $day['label'] }}: {{ $day['active'] }} active, {{ $day['completed'] }} completed, {{ $day['cancelled'] }} cancelled or no-show"
                            data-super-admin-booking-point="{{ $day['label'] }}"
                            x-on:mouseenter="hoveredDay = { label: '{{ $day['label'] }}', active: {{ $day['active'] }}, completed: {{ $day['completed'] }}, cancelled: {{ $day['cancelled'] }} }"
                            x-on:mouseleave="hoveredDay = null"
                            x-on:focus="hoveredDay = { label: '{{ $day['label'] }}', active: {{ $day['active'] }}, completed: {{ $day['completed'] }}, cancelled: {{ $day['cancelled'] }} }"
                            x-on:blur="hoveredDay = null"
                            class="cursor-crosshair outline-none"
                        >
                            <circle cx="{{ $day['x'] }}" cy="{{ $day['activeY'] }}" r="4" class="fill-zinc-50 stroke-violet-400 stroke-2 dark:fill-zinc-900" />
                            <circle cx="{{ $day['x'] }}" cy="{{ $day['completedY'] }}" r="4" class="fill-zinc-50 stroke-emerald-400 stroke-2 dark:fill-zinc-900" />
                            <circle cx="{{ $day['x'] }}" cy="{{ $day['cancelledY'] }}" r="4" class="fill-zinc-50 stroke-rose-400 stroke-2 dark:fill-zinc-900" />
                            <text x="{{ $day['x'] }}" y="207" text-anchor="middle" class="fill-zinc-600 text-xs dark:fill-zinc-300">{{ $day['label'] }}</text>
                        </g>
                    @endforeach
                </svg>
            </div>
            @endif
        </flux:card>

        <flux:card class="dashboard-reveal card-lift flex flex-col !rounded-2xl !border-gray-100 !bg-white !p-5 shadow-sm dark:!border-zinc-700 dark:!bg-zinc-900" data-super-admin-status-layout="reference">
            <div class="-mx-5 -mt-5 flex items-start justify-between gap-3 border-b border-gray-100 px-5 py-4 dark:border-zinc-800">
                <div>
                    <h2 class="text-lg font-bold tracking-tight text-gray-900 dark:text-white">Booking status</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-zinc-400">All bookings grouped by their latest status.</p>
                </div>
                <div class="flex items-center gap-2">
                    <span class="flex size-9 items-center justify-center rounded-xl bg-blue-50 text-blue-600 dark:bg-blue-500/10 dark:text-blue-300">
                        <flux:icon name="chart-pie" class="size-5" />
                    </span>
                    <x-super-admin.section-menu :actions="[
                        ['label' => __('Refresh section'), 'icon' => 'arrow-path', 'wire' => '$refresh'],
                        ['label' => __('View bookings'), 'icon' => 'arrow-top-right-on-square', 'href' => route(auth()->user()->operationsRouteName('module'), ['module' => 'service-bookings'])],
                    ]" />
                </div>
            </div>

            @php
                $statusTotal = array_sum(array_column($statusBreakdown, 'value'));
                $completedStatus = collect($statusBreakdown)->firstWhere('key', 'completed');
                $cancelledStatus = collect($statusBreakdown)->firstWhere('key', 'cancelled');
                $completedPercentage = $statusTotal > 0 ? (int) round($completedStatus['value'] / $statusTotal * 100) : 0;
                $cancelledPercentage = $statusTotal > 0 ? (int) round($cancelledStatus['value'] / $statusTotal * 100) : 0;
                $ringOffset = 0;
                $statusRingClasses = [
                    'violet' => ['stroke' => 'stroke-violet-400', 'dot' => 'bg-violet-400'],
                    'sky' => ['stroke' => 'stroke-sky-400', 'dot' => 'bg-sky-400'],
                    'blue' => ['stroke' => 'stroke-blue-400', 'dot' => 'bg-blue-400'],
                    'emerald' => ['stroke' => 'stroke-emerald-400', 'dot' => 'bg-emerald-400'],
                    'rose' => ['stroke' => 'stroke-rose-400', 'dot' => 'bg-rose-400'],
                ];
            @endphp

            <div class="mt-5 grid min-h-[15.625rem] min-w-0 flex-1 items-center gap-5" data-super-admin-booking-status-chart data-super-admin-status-chart-layout="reference">
                <svg viewBox="0 0 200 200" class="mx-auto aspect-square w-full max-w-48" role="img" aria-label="Booking status chart with {{ $statusTotal }} bookings: {{ $completedPercentage }} percent completed and {{ $cancelledPercentage }} percent cancelled or no-show">
                    <circle cx="100" cy="100" r="76" fill="none" class="stroke-gray-100 dark:stroke-zinc-800" stroke-width="20" />

                    @foreach ($statusBreakdown as $status)
                        @php($segmentPercentage = $statusTotal > 0 ? $status['value'] / $statusTotal * 100 : 0)
                        <circle
                            wire:key="dashboard-status-{{ $status['key'] }}"
                            data-super-admin-status-ring="{{ $status['key'] }}"
                            cx="100"
                            cy="100"
                            r="76"
                            fill="none"
                            pathLength="100"
                            stroke-dasharray="{{ $segmentPercentage }} {{ 100 - $segmentPercentage }}"
                            stroke-dashoffset="{{ -$ringOffset }}"
                            stroke-width="20"
                            class="{{ $statusRingClasses[$status['color']]['stroke'] }} transition-[stroke-dasharray] duration-500"
                            transform="rotate(-90 100 100)"
                        />
                        @php($ringOffset += $segmentPercentage)
                    @endforeach

                    <g data-super-admin-status-chart-center="summary">
                        <text x="100" y="97" text-anchor="middle" class="fill-zinc-950 text-[30px] font-bold dark:fill-white" data-super-admin-status-total>{{ number_format($statusTotal) }}</text>
                        <text x="100" y="118" text-anchor="middle" class="fill-zinc-500 text-[11px] font-semibold dark:fill-zinc-400">Total bookings</text>
                    </g>
                </svg>

                <div class="grid w-full min-w-0 grid-cols-1 gap-2 text-sm sm:grid-cols-2 lg:grid-cols-1" data-super-admin-status-legend>
                    @foreach ($statusBreakdown as $status)
                        <div wire:key="dashboard-status-legend-{{ $status['key'] }}" class="flex min-h-10 items-center gap-2.5 rounded-xl bg-zinc-50 px-3 py-2 dark:bg-white/[0.04]">
                            <span class="size-2.5 shrink-0 rounded-full {{ $statusRingClasses[$status['color']]['dot'] }}"></span>
                            <span class="min-w-0 flex-1 font-medium text-zinc-600 dark:text-zinc-300">{{ $status['label'] }}</span>
                            <span class="rounded-md bg-white px-1.5 py-0.5 text-xs font-semibold tabular-nums text-zinc-500 shadow-sm ring-1 ring-zinc-200/70 dark:bg-zinc-800 dark:text-zinc-300 dark:ring-white/10" data-super-admin-status-percentage="{{ $status['key'] }}">{{ $status['percentage'] }}%</span>
                            <span class="min-w-5 text-end font-bold tabular-nums text-zinc-900 dark:text-white">{{ $status['value'] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </flux:card>
    </div>

    <div class="dashboard-stagger grid gap-6 lg:grid-cols-3">
        <flux:card class="dashboard-reveal card-lift flex flex-col !rounded-xl !border-gray-100 !bg-white !p-5 shadow-sm lg:col-span-2 dark:!border-zinc-700 dark:!bg-zinc-900">
            <div class="-mx-5 -mt-5 flex flex-wrap items-center justify-between gap-3 border-b border-gray-100 px-5 py-4 dark:border-zinc-800">
                <div>
                    <h2 class="text-base font-semibold text-gray-900 dark:text-white">Recent bookings</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-zinc-400">The latest requests moving through FixTrack.</p>
                </div>
                <div class="flex items-center gap-1">
                    <flux:badge color="zinc" size="sm">Latest 6</flux:badge>
                    <a href="{{ route(auth()->user()->operationsRouteName('module'), ['module' => 'service-bookings']) }}" wire:navigate class="inline-flex items-center gap-1 text-sm font-semibold text-blue-600 transition-colors hover:text-blue-800 focus-visible:rounded focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-500 dark:text-blue-400 dark:hover:text-blue-300" data-admin-dashboard-view-bookings>
                        View all <flux:icon name="arrow-right" class="size-3.5" />
                    </a>
                    <x-super-admin.section-menu :actions="[
                        ['label' => __('Refresh section'), 'icon' => 'arrow-path', 'wire' => '$refresh'],
                    ]" />
                </div>
            </div>

            <div class="app-table-shell mt-4" data-app-table-shell>
            <flux:table data-super-admin-dashboard-table>
                <flux:table.columns>
                    <flux:table.column>Booking</flux:table.column>
                    <flux:table.column>Customer</flux:table.column>
                    <flux:table.column>Service</flux:table.column>
                    <flux:table.column>Status</flux:table.column>
                    <flux:table.column>Created</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @forelse ($recentBookings as $booking)
                        <flux:table.row wire:key="dashboard-booking-{{ $booking['id'] }}" class="transition-colors hover:bg-blue-50/50 dark:hover:bg-blue-500/10">
                            <flux:table.cell variant="strong" class="whitespace-normal break-words align-top">
                                <a href="{{ $booking['href'] }}" wire:navigate class="font-semibold text-gray-900 hover:text-blue-600 hover:underline focus-visible:rounded focus-visible:outline-2 focus-visible:outline-blue-500 dark:text-white dark:hover:text-blue-300">{{ $booking['reference'] }}</a>
                            </flux:table.cell>
                            <flux:table.cell class="whitespace-normal break-words align-top">{{ $booking['customer'] }}</flux:table.cell>
                            <flux:table.cell class="whitespace-normal break-words align-top">{{ $booking['service'] }}</flux:table.cell>
                            <flux:table.cell>
                                <div class="flex items-center gap-2">
                                    <x-super-admin.table-cell :value="$booking['status']" type="status" />
                                    @if ($booking['priority'])
                                        <flux:badge color="amber" size="sm">Priority</flux:badge>
                                    @endif
                                </div>
                            </flux:table.cell>
                            <flux:table.cell class="whitespace-nowrap text-zinc-500 dark:text-zinc-400">{{ $booking['createdAt'] }}</flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="5" class="py-0">
                                <div class="flex flex-col items-center justify-center px-6 py-14 text-center">
                                    <flux:icon name="clipboard-document-list" class="size-7 text-zinc-400" />
                                    <flux:heading size="lg" class="mt-3">No bookings yet</flux:heading>
                                    <flux:text class="mt-1.5 max-w-sm text-zinc-500 dark:text-zinc-400">No booking records yet.</flux:text>
                                </div>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
            </div>
        </flux:card>

        <flux:card class="dashboard-reveal card-lift flex flex-col !rounded-xl !border-gray-100 !bg-white !p-5 shadow-sm dark:!border-zinc-700 dark:!bg-zinc-900">
            <div class="-mx-5 -mt-5 flex items-start justify-between gap-3 border-b border-gray-100 px-5 py-4 dark:border-zinc-800">
                <div>
                    <h2 class="text-base font-semibold text-gray-900 dark:text-white">Top services</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-zinc-400">Most requested service types.</p>
                </div>
                <x-super-admin.section-menu :actions="[
                    ['label' => __('Refresh section'), 'icon' => 'arrow-path', 'wire' => '$refresh'],
                    ['label' => __('View bookings'), 'icon' => 'arrow-top-right-on-square', 'href' => route(auth()->user()->operationsRouteName('module'), ['module' => 'service-bookings'])],
                ]" />
            </div>

            <div class="mt-5 space-y-6">
                @forelse ($topServices as $service)
                    <div wire:key="dashboard-service-{{ $service['code'] }}">
                        <div class="mb-2 flex items-center justify-between gap-3 text-sm">
                            <span class="font-medium text-gray-700 dark:text-zinc-300">{{ $service['label'] }}</span>
                            <span class="font-bold tabular-nums text-gray-900 dark:text-white">{{ $service['value'] }}</span>
                        </div>
                        <div class="h-2 overflow-hidden rounded-full bg-gray-100 dark:bg-zinc-800" role="progressbar" aria-label="{{ $service['label'] }}" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $service['percentage'] }}">
                            <div class="h-full rounded-full bg-gradient-to-r from-cyan-400 to-blue-500" style="width: {{ $service['percentage'] }}%"></div>
                        </div>
                    </div>
                @empty
                    <div class="flex flex-col items-center justify-center py-12 text-center">
                        <flux:icon name="wrench-screwdriver" class="size-7 text-zinc-400" />
                        <flux:text class="mt-3 text-zinc-500 dark:text-zinc-400">Service demand will appear here.</flux:text>
                    </div>
                @endforelse
            </div>
        </flux:card>
    </div>

    <div class="dashboard-stagger grid gap-6 pb-6 lg:grid-cols-2">
        <flux:card class="dashboard-reveal card-lift !min-h-40 !rounded-xl !border-gray-100 !bg-white !p-5 shadow-sm dark:!border-zinc-700 dark:!bg-zinc-900">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h2 class="text-base font-semibold text-gray-900 dark:text-white">Needs attention</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-zinc-400">Requests and reviews waiting for a staff decision.</p>
                </div>
                <div class="flex items-center gap-1">
                    <flux:icon name="bell-alert" class="size-5 text-zinc-400" />
                    <x-super-admin.section-menu :actions="[
                        ['label' => __('Refresh section'), 'icon' => 'arrow-path', 'wire' => '$refresh'],
                        ['label' => __('View bookings'), 'icon' => 'arrow-top-right-on-square', 'href' => route(auth()->user()->operationsRouteName('module'), ['module' => 'service-bookings'])],
                    ]" />
                </div>
            </div>

            <div class="mt-4 divide-y divide-zinc-200 dark:divide-white/10">
                @forelse ($needsAttention as $item)
                    <div wire:key="dashboard-attention-{{ $item['key'] }}" class="flex items-center gap-3 py-3">
                        <div class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-amber-50 text-amber-600 dark:bg-amber-500/10 dark:text-amber-400">
                            <flux:icon name="exclamation-triangle" class="size-4" />
                        </div>
                        <div class="min-w-0 flex-1">
                            <a href="{{ $item['href'] }}" wire:navigate class="block truncate text-sm font-medium text-zinc-800 hover:text-blue-600 hover:underline focus-visible:rounded focus-visible:outline-2 focus-visible:outline-blue-500 dark:text-white dark:hover:text-blue-300">{{ $item['title'] }}</a>
                            <flux:text class="truncate text-xs text-zinc-500 dark:text-zinc-400">{{ $item['detail'] }}</flux:text>
                        </div>
                        <flux:badge :color="$item['color']" size="sm">{{ $item['status'] }}</flux:badge>
                    </div>
                @empty
                    <div class="flex flex-col items-center justify-center py-10 text-center">
                        <div class="flex size-16 items-center justify-center rounded-full border-4 border-white bg-emerald-50 text-emerald-500 shadow-[0_0_15px_rgba(16,185,129,0.15)] dark:border-zinc-900 dark:bg-emerald-500/10 dark:text-emerald-400">
                            <flux:icon name="check" class="size-7" />
                        </div>
                        <h3 class="mt-3 text-lg font-bold text-gray-800 dark:text-white">You're all caught up!</h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-zinc-400">No pending decisions right now.</p>
                    </div>
                @endforelse
            </div>
        </flux:card>

        <flux:card class="dashboard-reveal card-lift !rounded-xl !border-gray-100 !bg-white !p-5 shadow-sm dark:!border-zinc-700 dark:!bg-zinc-900">
            <div class="-mx-5 -mt-5 flex items-start justify-between gap-3 border-b border-gray-100 px-5 py-4 dark:border-zinc-800">
                <div>
                    <h2 class="text-base font-semibold text-gray-900 dark:text-white">System status</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-zinc-400">A quick read on the services behind the dashboard.</p>
                </div>
                <div class="flex items-center gap-1">
                    <flux:icon name="server-stack" class="size-5 text-zinc-400" />
                    <x-super-admin.section-menu :actions="$isAdministrator
                        ? [
                            ['label' => __('Refresh section'), 'icon' => 'arrow-path', 'wire' => '$refresh'],
                            ['label' => __('View system health'), 'icon' => 'arrow-top-right-on-square', 'href' => route('admin.module', ['module' => 'system-health'])],
                        ]
                        : [
                            ['label' => __('Refresh section'), 'icon' => 'arrow-path', 'wire' => '$refresh'],
                        ]" />
                </div>
            </div>

            <div class="mt-4 divide-y divide-zinc-200 dark:divide-white/10">
                @foreach ($systemStatus as $service)
                    <div wire:key="dashboard-system-{{ $loop->index }}" class="flex items-center gap-3 py-3">
                        <span class="size-2 shrink-0 rounded-full {{ $service['color'] === 'emerald' ? 'bg-emerald-400' : 'bg-amber-400' }}"></span>
                        <div class="min-w-0 flex-1">
                            <flux:text class="text-sm font-medium text-zinc-800 dark:text-white">{{ $service['label'] }}</flux:text>
                            <flux:text class="truncate text-xs text-zinc-500 dark:text-zinc-400">{{ $service['detail'] }}</flux:text>
                        </div>
                        <flux:badge :color="$service['color']" size="sm">{{ $service['value'] }}</flux:badge>
                    </div>
                @endforeach
            </div>
        </flux:card>
    </div>
</div>
