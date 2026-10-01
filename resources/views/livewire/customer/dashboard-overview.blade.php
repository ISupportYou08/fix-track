@php
    $currentBooking = $content['currentBooking'];
    $serviceColors = [
        ['bg-blue-50', 'text-blue-500'],
        ['bg-orange-50', 'text-orange-500'],
        ['bg-purple-50', 'text-purple-500'],
        ['bg-emerald-50', 'text-emerald-500'],
        ['bg-rose-50', 'text-rose-500'],
    ];
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

        <div class="flex items-center gap-3">
            <span class="rounded-lg bg-green-100 px-4 py-2 text-sm font-semibold text-emerald-800">Live overview</span>
            <button type="button" wire:click="startBooking" class="rounded-lg bg-[#1a1a1a] px-4 py-2 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-gray-800">
                + Book a technician
            </button>
        </div>
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
                    <p class="mb-6 max-w-xs text-sm text-gray-500">Book a technician when your home needs a hand.</p>
                    <button type="button" wire:click="startBooking" class="rounded-lg bg-[#1a1a1a] px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-gray-800">+ Book a service</button>
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
                    <button type="button" wire:key="recommended-service-{{ $service->code }}" wire:click="startBooking('{{ $service->code }}')" class="flex items-center justify-between rounded-xl border border-gray-100 p-3 text-left transition-colors hover:bg-gray-50">
                        <span class="flex min-w-0 items-center gap-4">
                            <span class="flex size-10 shrink-0 items-center justify-center rounded-lg {{ $serviceBackground }} {{ $serviceText }}"><flux:icon name="wrench-screwdriver" class="size-5" /></span>
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

    <div x-data="{ messagesOpen: false }" x-on:keydown.escape.window="messagesOpen = false" class="fixed bottom-6 right-6 z-50">
        <div id="customer-dashboard-messages" x-show="messagesOpen" x-cloak x-transition.origin.bottom.right role="region" aria-label="Messages" data-customer-message-panel class="absolute bottom-20 right-0 flex h-[min(42rem,calc(100dvh-7rem))] w-[min(30rem,calc(100vw-2rem))] flex-col overflow-hidden rounded-[1.75rem] border border-white/80 bg-white/95 shadow-[0_28px_80px_-24px_rgba(15,23,42,0.38)] ring-1 ring-slate-950/5 backdrop-blur-xl dark:border-white/10 dark:bg-slate-950/95 dark:ring-white/10">
            <div data-chat-panel-header class="flex shrink-0 items-center justify-between gap-3 border-b border-slate-200/70 bg-gradient-to-r from-white via-white to-indigo-50/70 px-5 py-4 dark:border-white/10 dark:from-slate-950 dark:via-slate-950 dark:to-indigo-950/50">
                <div class="flex min-w-0 items-center gap-3">
                    <span class="flex size-10 shrink-0 items-center justify-center rounded-2xl bg-slate-900 text-white shadow-[0_8px_20px_-8px_rgba(15,23,42,0.65)] dark:bg-indigo-500"><flux:icon name="chat-bubble-left-right" class="size-5" /></span>
                    <div class="min-w-0"><p class="text-[15px] font-semibold tracking-tight text-slate-950 dark:text-white">Messages</p><p class="truncate text-xs text-slate-500 dark:text-slate-400">Assistant and accepted bookings</p></div>
                </div>
                <button type="button" x-on:click="messagesOpen = false" class="flex size-9 shrink-0 items-center justify-center rounded-xl border border-slate-200/80 bg-white/80 text-slate-500 transition hover:border-slate-300 hover:bg-white hover:text-slate-900 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-500 dark:border-white/10 dark:bg-white/5 dark:text-slate-400 dark:hover:bg-white/10 dark:hover:text-white" aria-label="Close messages"><flux:icon name="x-mark" class="size-4" /></button>
            </div>
            <div class="min-h-0 flex-1"><livewire:booking-messenger :compact="true" :key="'customer-dashboard-messenger'" /></div>
        </div>

        <button type="button" x-on:click="messagesOpen = ! messagesOpen" x-bind:aria-expanded="messagesOpen.toString()" aria-controls="customer-dashboard-messages" class="group relative flex size-16 items-center justify-center rounded-[1.4rem] border border-white/20 bg-gradient-to-br from-indigo-500 via-indigo-600 to-violet-700 text-white shadow-[0_16px_36px_-10px_rgba(79,70,229,0.7)] ring-4 ring-white/80 transition duration-200 hover:-translate-y-1 hover:shadow-[0_20px_40px_-10px_rgba(79,70,229,0.8)] focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-indigo-600 dark:ring-slate-900" aria-label="{{ __('Open messages') }}">
            <flux:icon name="chat-bubble-left-right" class="size-7 transition-transform group-hover:scale-110" />
        </button>
    </div>
</section>
