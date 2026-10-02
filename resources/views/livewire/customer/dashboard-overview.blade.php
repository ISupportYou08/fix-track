@php
    $currentBooking = $content['currentBooking'];
    $serviceColors = [
        ['bg-blue-50', 'text-blue-500'],
        ['bg-orange-50', 'text-orange-500'],
        ['bg-purple-50', 'text-purple-500'],
        ['bg-emerald-50', 'text-emerald-500'],
        ['bg-rose-50', 'text-rose-500'],
    ];
    $serviceIcons = ['sparkles', 'fire', 'bolt', 'wrench-screwdriver', 'home-modern'];
@endphp

<section class="relative hidden h-dvh min-h-0 w-full flex-col gap-6 overflow-hidden bg-[#f8f9fa] p-8 font-sans text-gray-800 lg:flex" data-customer-reference-dashboard>
    <header class="flex shrink-0 items-end justify-between gap-6">
        <div>
            <div class="mb-2 flex items-center gap-2 text-sm text-gray-500">
                <span>Customer</span>
                <flux:icon name="chevron-right" class="size-3" />
                <span class="font-medium text-gray-800">Dashboard</span>
            </div>
            <h1 class="mb-1 text-3xl font-bold text-gray-900">Welcome back, {{ auth()->user()->name }}</h1>
            <p class="text-sm text-gray-500">Manage your bookings and repair services.</p>
        </div>

        <span class="rounded-lg bg-green-100 px-4 py-2 text-sm font-semibold text-emerald-800">Live overview</span>
    </header>

    <div class="flex min-h-0 flex-1 gap-6">
        <article class="group relative flex min-w-0 flex-1 flex-col overflow-hidden rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
            <div class="mb-2 flex items-start justify-between">
                <div>
                    <h2 class="text-lg font-bold text-gray-900">Current booking</h2>
                    <p class="text-xs text-gray-500">{{ $currentBooking?->status === 'completed' ? 'This service has been marked completed.' : 'Follow the next step in your home service.' }}</p>
                </div>
                @if ($currentBooking)
                    <span class="rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold text-gray-600">{{ \Illuminate\Support\Str::headline($currentBooking->status) }}</span>
                @else
                    <flux:icon name="ellipsis-vertical" class="size-5 text-gray-400" />
                @endif
            </div>

            @if ($currentBooking)
                <div class="flex flex-1 flex-col justify-center">
                    <div class="mb-5 flex items-center gap-4">
                        <span class="flex size-14 items-center justify-center rounded-full bg-gray-50 text-gray-500"><flux:icon name="wrench-screwdriver" class="size-6" /></span>
                        <div class="min-w-0">
                            <p class="text-xs font-semibold uppercase tracking-wider text-gray-600">{{ $currentBooking->reference }}</p>
                            <h3 class="truncate text-xl font-bold text-gray-900">{{ $serviceLabel($currentBooking->service_type) }}</h3>
                        </div>
                    </div>

                    <dl class="grid grid-cols-2 gap-x-6 gap-y-4 text-sm">
                        <div><dt class="text-xs text-gray-600">Technician</dt><dd class="mt-1 font-medium text-gray-800">{{ $currentBooking->technician?->name ?: 'Matching in progress' }}</dd></div>
                        <div><dt class="text-xs text-gray-600">Schedule</dt><dd class="mt-1 font-medium text-gray-800">{{ $formatDateTime($currentBooking->scheduled_at ?: $currentBooking->created_at) }}</dd></div>
                        <div class="col-span-2"><dt class="text-xs text-gray-600">Service address</dt><dd class="mt-1 truncate font-medium text-gray-800">{{ $currentBooking->address }}</dd></div>
                    </dl>

                    <div class="mt-6 grid grid-cols-5 gap-2">
                        @foreach ([
                            ['Confirmed', 'confirmed', in_array($currentBooking->status, ['pending', 'matching', 'assigned', 'en_route', 'in_progress', 'completed'], true)],
                            ['Matching', 'matching', in_array($currentBooking->status, ['matching', 'assigned', 'en_route', 'in_progress', 'completed'], true)],
                            ['On the way', 'on-the-way', in_array($currentBooking->status, ['en_route', 'in_progress', 'completed'], true)],
                            ['In progress', 'in-progress', in_array($currentBooking->status, ['in_progress', 'completed'], true)],
                            ['Completed', 'completed', $currentBooking->status === 'completed'],
                        ] as [$step, $stepKey, $done])
                            <div class="text-center" data-service-progress-step="{{ $stepKey }}" data-complete="{{ $done ? 'true' : 'false' }}">
                                <span class="mx-auto flex size-7 items-center justify-center rounded-full {{ $done ? 'bg-emerald-500 text-white' : 'border border-gray-300 text-gray-300' }}">
                                    @if ($done)<flux:icon name="check" class="size-3.5" />@endif
                                </span>
                                <span class="mt-1 block truncate text-xs {{ $done ? 'font-semibold text-gray-700' : 'text-gray-600' }}">{{ $step }}</span>
                            </div>
                        @endforeach
                    </div>

                    <div class="mt-6 flex gap-2">
                        <button type="button" wire:click="openBooking({{ $currentBooking->id }})" class="rounded-lg bg-[#1a1a1a] px-4 py-2 text-sm font-semibold text-white hover:bg-gray-800">View booking</button>
                        @if (in_array($currentBooking->status, \App\Models\Booking::ACTIVE_STATUSES, true))
                            <button type="button" wire:click="prepareCancellation({{ $currentBooking->id }})" class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-semibold text-gray-600 hover:bg-gray-50">Cancel booking</button>
                        @endif
                    </div>
                </div>
            @else
                <div class="flex flex-1 flex-col items-center justify-center text-center">
                    <span class="mb-4 flex size-16 items-center justify-center rounded-full bg-gray-50 text-gray-400"><flux:icon name="wrench-screwdriver" class="size-7" /></span>
                    <h3 class="mb-1 font-bold text-gray-800">No active service</h3>
                    <p class="max-w-xs text-sm text-gray-500">Book a technician when your home needs a hand.</p>
                </div>
            @endif
        </article>

        <article class="relative flex min-w-0 flex-1 flex-col overflow-hidden rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
            <div class="mb-6 flex items-start justify-between">
                <div>
                    <h2 class="text-lg font-bold text-gray-900">Top 5 Categories Recommended</h2>
                    <p class="text-xs text-gray-500">Services that match your profile.</p>
                </div>
                <flux:icon name="ellipsis-vertical" class="size-5 text-gray-400" />
            </div>

            <div class="flex flex-1 flex-col gap-3 overflow-y-auto pr-2">
                @forelse ($content['popularServices'] as $index => $service)
                    @php [$serviceBackground, $serviceText] = $serviceColors[$index % count($serviceColors)]; @endphp
                    <button type="button" wire:key="recommended-service-{{ $service->code }}" wire:click="startBooking('{{ $service->code }}')" class="flex items-center justify-between rounded-xl border border-gray-100 p-3 text-left transition-all duration-200 hover:-translate-y-0.5 hover:border-gray-200 hover:bg-gray-50 hover:shadow-sm">
                        <span class="flex min-w-0 items-center gap-4">
                            <span class="flex size-10 shrink-0 items-center justify-center rounded-lg {{ $serviceBackground }} {{ $serviceText }}"><flux:icon :name="$serviceIcons[$index % count($serviceIcons)]" class="size-5" /></span>
                            <span class="min-w-0">
                                <span class="block truncate text-sm font-semibold text-gray-800">{{ $service->name }}</span>
                                <span class="block truncate text-xs text-gray-500">{{ $service->description ?: $service->category }}</span>
                            </span>
                        </span>
                        <flux:icon name="arrow-right" class="size-4 shrink-0 text-gray-300" />
                    </button>
                @empty
                    <div class="flex flex-1 items-center justify-center text-sm text-gray-500">Service recommendations will appear here.</div>
                @endforelse
            </div>
        </article>
    </div>

    <div class="flex w-full flex-none items-center justify-center gap-6 pb-2 pt-4 lg:gap-12" data-customer-dashboard-booking-actions>
        <button type="button" wire:click="openBookingFlow('manual')" data-customer-dashboard-action="manual" class="flex h-16 max-w-[300px] flex-1 items-center justify-center gap-3 rounded-full border border-gray-200 bg-white font-bold text-gray-700 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:border-gray-300 hover:shadow-md focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-gray-800">
            <flux:icon name="calendar-days" class="size-6 text-gray-400" />
            <span>Manual Booking</span>
        </button>

        <button type="button" wire:click="openAiBookingFlow" data-customer-dashboard-action="ai" class="z-10 flex size-36 shrink-0 flex-col items-center justify-center rounded-full border-4 border-white bg-emerald-500 text-white shadow-lg transition-all duration-300 hover:scale-105 hover:bg-emerald-400 hover:shadow-xl focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-emerald-600" aria-label="Scan or upload an item image">
            <flux:icon name="camera" class="mb-1 size-7" />
            <span class="text-xl font-bold leading-tight">AI<br>Scan</span>
        </button>

        <button type="button" wire:click="openWalkInBookingFlow" data-customer-dashboard-action="walk-in" class="flex h-16 max-w-[300px] flex-1 items-center justify-center gap-3 rounded-full border border-gray-200 bg-white font-bold text-gray-700 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:border-gray-300 hover:shadow-md focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-gray-800">
            <flux:icon name="queue-list" class="size-6 text-gray-400" />
            <span>Walk In Booking</span>
        </button>
    </div>

    <div x-data="{ messagesOpen: false }" x-on:keydown.escape.window="messagesOpen = false" class="fixed bottom-6 right-6 z-50" data-customer-reference-chat-widget>
        <div id="customer-dashboard-messages" x-show="messagesOpen" x-cloak x-transition.origin.bottom.right role="region" aria-label="FixTrack Support" data-customer-message-panel class="absolute bottom-28 right-0 flex h-[min(500px,calc(100dvh-7rem))] w-[min(400px,calc(100vw-2rem))] flex-col overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-2xl dark:border-white/10 dark:bg-slate-950">
            <div data-chat-panel-header class="flex shrink-0 items-center justify-between gap-3 border-b border-gray-100 bg-white px-4 py-3 shadow-sm dark:border-white/10 dark:bg-slate-950">
                <div class="flex min-w-0 items-center gap-2.5">
                    <span class="size-2 shrink-0 animate-pulse rounded-full bg-emerald-500"></span>
                    <div class="min-w-0"><p class="text-sm font-semibold text-gray-800 dark:text-white">FixTrack Support</p><p class="truncate text-xs text-gray-500 dark:text-slate-400">Assistant and accepted booking chats</p></div>
                </div>
                <button type="button" x-on:click="messagesOpen = false" class="flex size-8 shrink-0 items-center justify-center rounded-lg text-gray-400 transition-colors hover:bg-gray-50 hover:text-gray-600 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-500 dark:hover:bg-white/10 dark:hover:text-white" aria-label="Close messages"><flux:icon name="x-mark" class="size-5" /></button>
            </div>
            <div class="min-h-0 flex-1"><livewire:booking-messenger :compact="true" :key="'customer-dashboard-messenger'" /></div>
        </div>

        <button type="button" x-on:click="messagesOpen = ! messagesOpen" x-bind:aria-expanded="messagesOpen.toString()" aria-controls="customer-dashboard-messages" class="group relative flex size-16 cursor-pointer items-center justify-center rounded-full rounded-br-[4px] bg-blue-500 text-white shadow-lg transition-all duration-200 hover:scale-105 hover:bg-blue-600 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-blue-600" aria-label="{{ __('Open FixTrack Support') }}">
            <flux:icon name="chat-bubble-left-right" class="size-8 transition-transform group-hover:scale-110" />
        </button>
    </div>
</section>
