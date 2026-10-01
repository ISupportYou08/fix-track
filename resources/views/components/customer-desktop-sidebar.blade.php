@php
    $groups = [
        ['heading' => 'Home', 'items' => [
            ['label' => 'Dashboard', 'icon' => 'home', 'href' => route('customer.module'), 'slug' => null],
        ]],
        ['heading' => 'Bookings', 'items' => [
            ['label' => 'Book a Service', 'icon' => 'calendar-days', 'href' => route('customer.module', ['module' => 'book-service']), 'slug' => 'book-service'],
            ['label' => 'My Bookings', 'icon' => 'clipboard-document-list', 'href' => route('customer.module', ['module' => 'my-bookings']), 'slug' => 'my-bookings'],
            ['label' => 'Walk-in Queue', 'icon' => 'queue-list', 'href' => route('customer.module', ['module' => 'walk-in-queue']), 'slug' => 'walk-in-queue'],
        ]],
        ['heading' => 'Service Journey', 'items' => [
            ['label' => 'Quotations', 'icon' => 'document-text', 'href' => route('customer.module', ['module' => 'quotations']), 'slug' => 'quotations'],
            ['label' => 'Payments', 'icon' => 'credit-card', 'href' => route('customer.module', ['module' => 'payments']), 'slug' => 'payments'],
        ]],
        ['heading' => 'Account', 'items' => [
            ['label' => 'Ratings & Reviews', 'icon' => 'star', 'href' => route('customer.module', ['module' => 'ratings-reviews']), 'slug' => 'ratings-reviews'],
            ['label' => 'Notifications', 'icon' => 'bell', 'href' => route('customer.module', ['module' => 'notifications']), 'slug' => 'notifications'],
            ['label' => 'Support & Help', 'icon' => 'question-mark-circle', 'href' => route('customer.module', ['module' => 'support']), 'slug' => 'support'],
            ['label' => 'Settings', 'icon' => 'cog-6-tooth', 'href' => route('customer.module', ['module' => 'settings']), 'slug' => 'settings'],
        ]],
    ];
    $activeModule = request()->route('module');
@endphp

<div class="flex h-full min-h-0 flex-col bg-white text-gray-800">
    <div class="flex min-h-0 flex-1 flex-col overflow-hidden pt-6">
        <div class="mb-8 flex items-center justify-between px-6">
            <a href="{{ route('customer.module') }}" wire:navigate class="flex items-center gap-3" aria-label="{{ __('FixTrack dashboard') }}">
                <span class="flex size-8 items-center justify-center rounded bg-[#1a1a1a] text-white shadow-sm">
                    <flux:icon name="wrench-screwdriver" class="size-4" />
                </span>
                <span class="text-lg font-bold text-[#1a1a1a]">FixTrack</span>
            </a>
            <flux:sidebar.collapse class="text-gray-400 hover:text-gray-600" />
        </div>

        <nav class="flex flex-1 flex-col gap-6 overflow-y-auto px-4 pb-4">
            @foreach ($groups as $group)
                <section>
                    <h2 class="mb-2 px-3 text-xs font-semibold uppercase tracking-wider text-gray-400">{{ $group['heading'] }}</h2>
                    <div class="space-y-1">
                        @foreach ($group['items'] as $item)
                            @php $isCurrent = $item['slug'] === null ? in_array($activeModule, [null, 'overview'], true) : $activeModule === $item['slug']; @endphp
                            <a
                                href="{{ $item['href'] }}"
                                wire:navigate
                                data-customer-sidebar-item="{{ $item['slug'] ?? 'dashboard' }}"
                                class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors {{ $isCurrent ? 'bg-gray-50 text-[#1a1a1a]' : 'text-gray-600 hover:bg-gray-50 hover:text-[#1a1a1a]' }}"
                            >
                                <flux:icon :name="$item['icon']" class="size-5 text-gray-500" />
                                <span>{{ $item['label'] }}</span>
                            </a>
                        @endforeach
                    </div>
                </section>
            @endforeach
        </nav>
    </div>

    <flux:dropdown position="top" align="start">
        <button type="button" class="flex w-full items-center justify-between border-t border-gray-100 p-4 text-left transition-colors hover:bg-gray-50">
            <span class="flex min-w-0 items-center gap-3">
                <span class="flex size-9 shrink-0 items-center justify-center rounded-full bg-gray-200 text-sm font-bold text-gray-600">{{ auth()->user()->initials() }}</span>
                <span class="truncate text-sm font-semibold text-gray-800">{{ auth()->user()->name }}</span>
            </span>
            <flux:icon name="chevron-up" class="size-3.5 text-gray-400" />
        </button>

        <flux:menu class="w-56">
            <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>{{ __('Profile settings') }}</flux:menu.item>
            <flux:menu.separator />
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full cursor-pointer">{{ __('Log out') }}</flux:menu.item>
            </form>
        </flux:menu>
    </flux:dropdown>
</div>
