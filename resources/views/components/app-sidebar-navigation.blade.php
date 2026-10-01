@props([
    'mobile' => false,
])

@php
    $isOperationsUser = auth()->user()->canAccessOperationsWorkspace();
    $isAdministrator = auth()->user()->isSuperAdmin();
    $operationsRoutePrefix = auth()->user()->isStaff() ? 'staff' : 'admin';
    $isTechnician = auth()->user()->isTechnician();
    $staffNavigationBadges ??= [];
    $moduleRoute = $isOperationsUser ? $operationsRoutePrefix.'.module' : ($isTechnician ? 'technician.module' : 'customer.module');
    $dashboardRoute = $isOperationsUser ? route($operationsRoutePrefix.'.dashboard') : ($isTechnician ? route('technician.module') : route('customer.module'));

    $operationsGroups = $isAdministrator
        ? [
            [
                'heading' => __('Overview'),
                'items' => [
                    ['label' => __('Dashboard'), 'icon' => 'home', 'href' => $dashboardRoute, 'current' => request()->routeIs('dashboard', 'admin.dashboard', 'staff.dashboard')],
                ],
            ],
            [
                'heading' => __('User Management'),
                'items' => [
                    ['label' => __('All Users'), 'icon' => 'users', 'slug' => 'users-roles'],
                    ['label' => __('Technician Applications'), 'icon' => 'identification', 'slug' => 'technician-verification'],
                ],
            ],
            [
                'heading' => __('Service Operations'),
                'items' => [
                    ['label' => __('Service Bookings'), 'icon' => 'clipboard-document-list', 'slug' => 'service-bookings'],
                    ['label' => __('Dispatch Monitor'), 'icon' => 'map', 'slug' => 'dispatch-monitor'],
                    ['label' => __('Walk-in Queue'), 'icon' => 'queue-list', 'slug' => 'walk-in-queue'],
                ],
            ],
            [
                'heading' => __('Finance & Insights'),
                'items' => [
                    ['label' => __('Payments & Revenue'), 'icon' => 'banknotes', 'slug' => 'payments-revenue'],
                    ['label' => __('Ratings & Reviews'), 'icon' => 'star', 'slug' => 'ratings-reviews'],
                    ['label' => __('Reports & Analytics'), 'icon' => 'chart-bar', 'slug' => 'reports-analytics'],
                ],
            ],
            [
                'heading' => __('Administration'),
                'items' => [
                    ['label' => __('Support & Disputes'), 'icon' => 'chat-bubble-left-right', 'slug' => 'support-disputes'],
                    ['label' => __('Service Catalog'), 'icon' => 'wrench-screwdriver', 'slug' => 'service-catalog'],
                    ['label' => __('Audit Logs'), 'icon' => 'document-text', 'slug' => 'audit-logs'],
                    ['label' => __('System Health'), 'icon' => 'server-stack', 'slug' => 'system-health'],
                    ['label' => __('Platform Settings'), 'icon' => 'cog-6-tooth', 'slug' => 'platform-settings'],
                ],
            ],
        ]
        : [
            [
                'heading' => __('Workspace'),
                'items' => [
                    ['label' => __('Dashboard'), 'icon' => 'home', 'href' => $dashboardRoute, 'current' => request()->routeIs('staff.dashboard')],
                ],
            ],
            [
                'heading' => __('Service Operations'),
                'items' => [
                    ['label' => __('Service Bookings'), 'icon' => 'clipboard-document-list', 'slug' => 'service-bookings', 'badge' => $staffNavigationBadges['service-bookings'] ?? 0],
                    ['label' => __('Dispatch & Availability'), 'icon' => 'map', 'slug' => 'dispatch-monitor', 'badge' => $staffNavigationBadges['dispatch-monitor'] ?? 0],
                    ['label' => __('Walk-in Queue'), 'icon' => 'queue-list', 'slug' => 'walk-in-queue', 'badge' => $staffNavigationBadges['walk-in-queue'] ?? 0],
                ],
            ],
            [
                'heading' => __('Technicians'),
                'items' => [
                    ['label' => __('Technician Applications'), 'icon' => 'identification', 'slug' => 'technician-verification', 'badge' => $staffNavigationBadges['technician-verification'] ?? 0],
                ],
            ],
            [
                'heading' => __('Customer Care'),
                'items' => [
                    ['label' => __('Support & Disputes'), 'icon' => 'chat-bubble-left-right', 'slug' => 'support-disputes', 'badge' => $staffNavigationBadges['support-disputes'] ?? 0],
                    ['label' => __('Ratings & Reviews'), 'icon' => 'star', 'slug' => 'ratings-reviews'],
                ],
            ],
            [
                'heading' => __('Finance'),
                'items' => [
                    ['label' => __('Payments & Revenue'), 'icon' => 'banknotes', 'slug' => 'payments-revenue', 'badge' => $staffNavigationBadges['payments-revenue'] ?? 0],
                ],
            ],
            [
                'heading' => __('Tools'),
                'items' => [
                    ['label' => __('Reports & Analytics'), 'icon' => 'chart-bar', 'slug' => 'reports-analytics'],
                    ['label' => __('Service Catalog'), 'icon' => 'wrench-screwdriver', 'slug' => 'service-catalog'],
                ],
            ],
        ];

    $groups = $isOperationsUser
        ? $operationsGroups
        : ($isTechnician
            ? [
                [
                    'heading' => __('Technician Workspace'),
                    'items' => [
                        ['label' => __('Dashboard'), 'icon' => 'home', 'href' => $dashboardRoute, 'current' => request()->routeIs('technician.module') && ! request()->route('module')],
                        ['label' => __('Job Requests'), 'icon' => 'queue-list', 'slug' => 'job-requests'],
                        ['label' => __('My Jobs'), 'icon' => 'clipboard-document-list', 'slug' => 'my-jobs'],
                        ['label' => __('Schedule'), 'icon' => 'calendar-days', 'slug' => 'schedule'],
                    ],
                ],
                [
                    'heading' => __('Operations'),
                    'items' => [
                        ['label' => __('Dispatch & Routes'), 'icon' => 'map', 'slug' => 'dispatch-routes'],
                        ['label' => __('Walk-In'), 'icon' => 'ticket', 'slug' => 'walk-in'],
                    ],
                ],
                [
                    'heading' => __('Finance & Performance'),
                    'items' => [
                        ['label' => __('Earnings'), 'icon' => 'banknotes', 'slug' => 'earnings'],
                        ['label' => __('Ratings & Reviews'), 'icon' => 'star', 'slug' => 'ratings-reviews'],
                    ],
                ],
                [
                    'heading' => __('Account'),
                    'items' => [
                        ['label' => __('Verification & Profile'), 'icon' => 'user-circle', 'slug' => 'verification-profile'],
                        ['label' => __('Notifications'), 'icon' => 'bell', 'slug' => 'notifications'],
                        ['label' => __('Messages'), 'icon' => 'chat-bubble-left-right', 'slug' => 'messages'],
                        ['label' => __('Support & Help'), 'icon' => 'chat-bubble-left-right', 'slug' => 'support'],
                    ],
                ],
            ]
            : [
                [
                    'heading' => __('Home'),
                    'items' => [
                        ['label' => __('Dashboard'), 'icon' => 'home', 'href' => $dashboardRoute, 'current' => request()->routeIs('customer.module') && ! request()->route('module')],
                    ],
                ],
                [
                    'heading' => __('Bookings'),
                    'items' => [
                        ['label' => __('Book a Service'), 'icon' => 'plus-circle', 'slug' => 'book-service'],
                        ['label' => __('My Bookings'), 'icon' => 'clipboard-document-list', 'slug' => 'my-bookings'],
                        ['label' => __('Walk-in Queue'), 'icon' => 'queue-list', 'slug' => 'walk-in-queue'],
                    ],
                ],
                [
                    'heading' => __('Service Journey'),
                    'items' => [
                        ['label' => __('Quotations'), 'icon' => 'document-text', 'slug' => 'quotations'],
                        ['label' => __('Payments'), 'icon' => 'credit-card', 'slug' => 'payments'],
                    ],
                ],
                [
                    'heading' => __('Account'),
                    'items' => [
                        ['label' => __('Ratings & Reviews'), 'icon' => 'star', 'slug' => 'ratings-reviews'],
                        ['label' => __('Notifications'), 'icon' => 'bell', 'slug' => 'notifications'],
                        ['label' => __('Messages'), 'icon' => 'chat-bubble-left-right', 'slug' => 'messages'],
                        ['label' => __('Support & Help'), 'icon' => 'chat-bubble-left-right', 'slug' => 'support'],
                        ['label' => __('Settings'), 'icon' => 'cog', 'slug' => 'settings'],
                    ],
                ],
        ]);
    $navigationItems = collect($groups)
        ->flatMap(fn ($group) => $group['items'])
        ->map(function (array $item) use ($moduleRoute): array {
            $item['key'] = $item['slug'] ?? 'dashboard';
            $item['href'] = $item['href'] ?? route($moduleRoute, ['module' => $item['slug']]);
            $item['current'] = $item['current'] ?? (isset($item['slug']) && request()->routeIs($moduleRoute) && request()->route('module') === $item['slug']);
            $item['badgeLabel'] = ($item['badge'] ?? 0) > 99 ? '99+' : (($item['badge'] ?? 0) > 0 ? (string) $item['badge'] : null);

            return $item;
        })
        ->values();

    $mobilePrimaryKeys = $isOperationsUser
        ? ($isAdministrator
            ? ['dashboard', 'users-roles', 'technician-verification', 'service-bookings']
            : ['dashboard', 'service-bookings', 'dispatch-monitor', 'walk-in-queue'])
        : ($isTechnician
            ? ['dashboard', 'job-requests', 'my-jobs', 'dispatch-routes', 'verification-profile']
            : ['dashboard', 'book-service', 'my-bookings', 'quotations']);
    $isCustomer = ! $isOperationsUser && ! $isTechnician;
    $customerMobileItems = collect([
        [
            'key' => 'home',
            'label' => __('Home'),
            'icon' => 'home',
            'href' => $dashboardRoute,
            'current' => request()->routeIs('customer.module') && in_array(request()->route('module'), [null, 'overview'], true),
        ],
        [
            'key' => 'activity',
            'label' => __('Activity'),
            'icon' => 'clipboard-document-list',
            'href' => route($moduleRoute, ['module' => 'my-bookings']),
            'current' => request()->routeIs('customer.module') && in_array(request()->route('module'), ['my-bookings', 'walk-in-queue', 'quotations', 'payments'], true),
        ],
        [
            'key' => 'messages',
            'label' => __('Messages'),
            'icon' => 'chat-bubble-left-right',
            'href' => route($moduleRoute, ['module' => 'messages']),
            'current' => request()->routeIs('customer.module') && in_array(request()->route('module'), ['messages', 'notifications', 'support'], true),
        ],
        [
            'key' => 'account',
            'label' => __('Account'),
            'icon' => 'user-circle',
            'href' => route($moduleRoute, ['module' => 'settings']),
            'current' => request()->routeIs('customer.module') && in_array(request()->route('module'), ['settings', 'ratings-reviews'], true),
        ],
    ]);
    $mobileLabels = [
        'dashboard' => __('Home'),
        'service-bookings' => __('Bookings'),
        'dispatch-monitor' => __('Dispatch'),
        'users-roles' => __('Users'),
        'technician-verification' => __('Technicians'),
        'walk-in-queue' => __('Walk-ins'),
        'job-requests' => __('Requests'),
        'my-jobs' => __('Jobs'),
        'dispatch-routes' => __('Routes'),
        'verification-profile' => __('Profile'),
        'book-service' => __('Book'),
        'my-bookings' => __('Bookings'),
        'quotations' => __('Quotes'),
    ];
    $mobileItems = $isCustomer
        ? $customerMobileItems
        : $navigationItems
            ->filter(fn (array $item): bool => in_array($item['key'], $mobilePrimaryKeys, true))
            ->map(function (array $item) use ($mobileLabels): array {
                $item['mobileLabel'] = $mobileLabels[$item['key']] ?? $item['label'];

                return $item;
            });
    $mobileMoreItems = $isCustomer
        ? collect()
        : $navigationItems->reject(fn (array $item): bool => in_array($item['key'], $mobilePrimaryKeys, true));
    $mobileMoreCurrent = $mobileMoreItems->contains(fn (array $item): bool => $item['current']);
@endphp

@if ($mobile)
    <nav
        class="fixed inset-x-0 bottom-0 z-40 border-t border-zinc-200 bg-white shadow-[0_-8px_24px_-20px_rgb(0_0_0_/_0.35)] dark:border-white/10 dark:bg-zinc-950 lg:hidden"
        style="padding-bottom: max(0.5rem, env(safe-area-inset-bottom));"
        data-app-mobile-navigation
    >
        <flux:navbar class="mx-auto flex h-[4.5rem] max-w-screen-sm items-stretch justify-between gap-1 px-2 !py-2">
            @foreach ($mobileItems as $item)
                <flux:navbar.item
                    :href="$item['href']"
                    :current="$item['current']"
                    :icon="$item['icon']"
                    :badge="$item['badgeLabel'] ?? null"
                    wire:navigate
                    data-app-mobile-nav-item="{{ $item['key'] }}"
                    class="!h-full min-w-0 flex-1 flex-col justify-center gap-1 rounded-xl px-1 py-2 text-center text-[0.8125rem] leading-none data-current:bg-zinc-100 dark:data-current:bg-white/10 [&>div>svg]:size-5 [&_[data-content]]:ms-0 [&_[data-content]]:flex-none [&_[data-content]]:text-[0.8125rem] [&_[data-content]]:font-semibold"
                >
                    {{ $item['mobileLabel'] ?? $item['label'] }}
                </flux:navbar.item>
            @endforeach

            @if (! $isCustomer && ! $isTechnician)
                <flux:dropdown position="top" align="end">
                <flux:navbar.item
                    icon="ellipsis-horizontal"
                    :current="$mobileMoreCurrent"
                    data-app-mobile-nav-item="more"
                    class="!h-full min-w-0 flex-1 flex-col justify-center gap-1 rounded-xl px-1 py-2 text-center text-xs leading-none data-current:bg-zinc-100 dark:data-current:bg-white/10 [&>div>svg]:size-5 [&_[data-content]]:ms-0 [&_[data-content]]:flex-none [&_[data-content]]:text-xs [&_[data-content]]:font-medium"
                >
                    {{ __('More') }}
                </flux:navbar.item>

                <flux:menu class="mb-2 max-h-[70vh] overflow-y-auto">
                    @foreach ($mobileMoreItems as $item)
                        <flux:menu.item :href="$item['href']" :icon="$item['icon']" wire:navigate>
                            {{ $item['label'] }}
                        </flux:menu.item>
                    @endforeach

                    <flux:menu.separator />

                    <form method="POST" action="{{ route('logout') }}" class="w-full" data-app-mobile-logout>
                        @csrf
                        <flux:menu.item
                            as="button"
                            type="submit"
                            icon="arrow-right-start-on-rectangle"
                            class="w-full cursor-pointer"
                            data-test="logout-button"
                        >
                            {{ __('Log out') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
                </flux:dropdown>
            @endif
        </flux:navbar>
    </nav>
@else
    @if ($isOperationsUser)
        <nav aria-label="{{ auth()->user()->isStaff() ? __('Staff navigation') : __('Administrator navigation') }}" data-admin-sidebar-navigation class="flex min-h-0 flex-1 flex-col gap-5 overflow-y-auto px-3 py-4 in-data-flux-sidebar-collapsed-desktop:items-center in-data-flux-sidebar-collapsed-desktop:px-2">
            <div class="rounded-xl border border-blue-100 bg-blue-50 px-3 py-3 dark:border-blue-500/20 dark:bg-blue-500/10 in-data-flux-sidebar-collapsed-desktop:hidden" @if (auth()->user()->isStaff()) data-staff-workspace-label @endif>
                <p class="text-sm font-bold text-blue-900 dark:text-blue-100">{{ auth()->user()->isStaff() ? __('Staff Workspace') : __('Administrator Console') }}</p>
                <p class="mt-0.5 text-xs leading-5 text-blue-700/80 dark:text-blue-300/80">{{ auth()->user()->isStaff() ? __('Daily service operations') : __('Platform management') }}</p>
            </div>
            @foreach ($groups as $group)
                <section x-data="{ open: true }" data-admin-sidebar-section class="w-full">
                    <button type="button" x-on:click="open = ! open" x-bind:aria-expanded="open.toString()" aria-controls="admin-sidebar-group-{{ $loop->index }}" class="group mb-1.5 flex w-full items-center justify-between rounded-md px-2 py-1 text-left text-sm font-semibold tracking-wide text-gray-500 transition-colors hover:text-gray-700 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-500 dark:text-zinc-400 dark:hover:text-zinc-200 in-data-flux-sidebar-collapsed-desktop:hidden">
                        <span>{{ $group['heading'] }}</span>
                        <flux:icon name="chevron-down" class="size-3 text-gray-400 transition-transform duration-200 group-hover:text-gray-600 dark:text-zinc-500" x-bind:class="{ '-rotate-90': ! open }" />
                    </button>
                    <div id="admin-sidebar-group-{{ $loop->index }}" x-show="open" x-transition class="flex flex-col gap-1 in-data-flux-sidebar-collapsed-desktop:!flex">
                        @foreach ($group['items'] as $item)
                            @php
                                $href = $item['href'] ?? route($moduleRoute, ['module' => $item['slug']]);
                                $current = $item['current'] ?? (request()->routeIs($moduleRoute) && request()->route('module') === $item['slug']);
                                $sidebarItemKey = $item['slug'] ?? 'dashboard';
                            @endphp
                            <a href="{{ $href }}" wire:navigate data-super-admin-sidebar-item="{{ $sidebarItemKey }}" data-sidebar-item="{{ $sidebarItemKey }}" @if ($current) aria-current="page" data-current @endif title="{{ $item['label'] }}" class="flex min-h-11 items-center gap-3 rounded-lg border px-3 py-2.5 text-base font-medium transition-colors focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-500 in-data-flux-sidebar-collapsed-desktop:size-11 in-data-flux-sidebar-collapsed-desktop:justify-center in-data-flux-sidebar-collapsed-desktop:px-0 {{ $current ? 'border-gray-200/60 bg-slate-100 text-gray-900 shadow-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-white' : 'border-transparent text-gray-600 hover:bg-gray-50 hover:text-gray-900 dark:text-zinc-300 dark:hover:bg-zinc-800 dark:hover:text-white' }}">
                                <flux:icon :name="$item['icon']" class="size-5 shrink-0 {{ $current ? 'text-blue-500 dark:text-blue-400' : 'text-gray-400 dark:text-zinc-500' }}" />
                                <span class="min-w-0 flex-1 truncate in-data-flux-sidebar-collapsed-desktop:hidden">{{ $item['label'] }}</span>
                                @if (($item['badge'] ?? 0) > 0)
                                    <span class="inline-flex min-w-6 items-center justify-center rounded-full bg-blue-100 px-2 py-0.5 text-xs font-bold tabular-nums text-blue-700 dark:bg-blue-500/20 dark:text-blue-200 in-data-flux-sidebar-collapsed-desktop:hidden" data-staff-nav-badge="{{ $sidebarItemKey }}">{{ ($item['badge'] ?? 0) > 99 ? '99+' : $item['badge'] }}</span>
                                @endif
                            </a>
                        @endforeach
                    </div>
                </section>
            @endforeach
        </nav>
    @else
        @foreach ($groups as $group)
            <flux:sidebar.group :heading="$group['heading']" class="grid">
                @foreach ($group['items'] as $item)
                    @php
                        $href = $item['href'] ?? (isset($item['slug']) ? route($moduleRoute, ['module' => $item['slug']]) : null);
                        $current = $item['current'] ?? (isset($item['slug']) && request()->routeIs($moduleRoute) && request()->route('module') === $item['slug']);
                        $sidebarItemKey = $item['slug'] ?? 'dashboard';
                    @endphp

                    @if ($href !== null)
                        <flux:sidebar.item
                            :icon="$item['icon']"
                            :href="$href"
                            :current="$current"
                            data-super-admin-sidebar-item="{{ $sidebarItemKey }}"
                            data-technician-sidebar-item="{{ $sidebarItemKey }}"
                            data-customer-sidebar-item="{{ $sidebarItemKey }}"
                            data-sidebar-item="{{ $sidebarItemKey }}"
                            wire:navigate
                        >
                            {{ $item['label'] }}
                        </flux:sidebar.item>
                    @endif
                @endforeach
            </flux:sidebar.group>
        @endforeach
    @endif
@endif
