@php
    $formatDate = static fn ($date): string => $date ? \Illuminate\Support\Carbon::parse($date)->format('M d, Y') : '—';
    $formatDateTime = static fn ($date): string => $date ? \Illuminate\Support\Carbon::parse($date)->timezone('Asia/Manila')->format('M d, Y · g:i A') : '—';
    $serviceLabel = static fn (?string $service): string => $serviceCatalog->firstWhere('code', $service)?->name
        ?? \Illuminate\Support\Str::headline((string) $service);
    $paymentMethodLabel = static fn (?string $method): string => 'Cash';
    $realtimeInterval = match ($moduleSlug) {
        'walk-in-queue' => 5000,
        'overview' => 1000,
        'my-bookings' => 10000,
        'payments' => 60000,
        default => null,
    };
    $realtimeScope = $realtimeInterval ? "customer-{$moduleSlug}" : null;
    $mobileTab = match ($moduleSlug) {
        'overview' => 'home',
        'my-bookings', 'walk-in-queue', 'quotations', 'payments' => 'activity',
        'notifications', 'support' => 'messages',
        'settings', 'ratings-reviews' => 'account',
        default => null,
    };
@endphp

<div
    class="w-full"
    @if ($realtimeScope)
        data-realtime-scope="{{ $realtimeScope }}"
        data-realtime-url="{{ route('realtime.snapshot', ['scope' => $realtimeScope]) }}"
        data-realtime-interval="{{ $realtimeInterval }}"
    @endif
>
    @if ($mobileTab)
        @include('livewire.customer.mobile-shell', ['tab' => $mobileTab])
    @endif

    <div class="{{ $mobileTab ? 'hidden lg:flex' : 'flex' }} w-full flex-col gap-6 p-6 lg:p-8">
        <div class="dashboard-reveal">
        <flux:breadcrumbs>
            <flux:breadcrumbs.item :href="route('customer.module')" wire:navigate>Customer</flux:breadcrumbs.item>
            <flux:breadcrumbs.item>{{ $module['label'] }}</flux:breadcrumbs.item>
        </flux:breadcrumbs>

        <div class="mt-4 flex flex-wrap items-center justify-between gap-4">
            <div class="min-w-0">
                <flux:heading size="xl" level="1">{{ $moduleSlug === 'overview' ? 'Welcome back, '.auth()->user()->name : $module['label'] }}</flux:heading>
                <flux:text class="mt-1 text-zinc-500 dark:text-zinc-400">{{ $moduleSlug === 'overview' ? 'Manage your bookings and repair services.' : $module['description'] }}</flux:text>
            </div>

            <div class="flex items-center gap-2">
                <flux:badge :color="$moduleState['color']" size="lg">{{ $moduleState['label'] }}</flux:badge>
                @if ($moduleSlug !== 'book-service')
                    <flux:button size="sm" variant="primary" icon="plus" wire:click="startBooking">Book a technician</flux:button>
                @endif
            </div>
        </div>
    </div>

    <div class="dashboard-stagger grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ($content['stats'] as $stat)
            <flux:card class="dashboard-reveal card-lift shadow-sm !border-s-4 !border-s-violet-400">
                <div class="flex items-center gap-4">
                    <div class="flex size-11 shrink-0 items-center justify-center rounded-lg bg-violet-50 text-violet-600 dark:bg-violet-500/10 dark:text-violet-300">
                        <flux:icon :name="$stat['icon']" class="size-5" />
                    </div>
                    <div class="min-w-0">
                        <flux:text class="text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ $stat['label'] }}</flux:text>
                        <flux:heading class="mt-1 truncate text-2xl tracking-tight">{{ $stat['value'] }}</flux:heading>
                    </div>
                </div>
            </flux:card>
        @endforeach
    </div>

    @if ($moduleSlug === 'overview')
        @php $currentBooking = $content['currentBooking']; @endphp
        @if ($content['currentWalkIn'])
            @php $currentWalkIn = $content['currentWalkIn']; @endphp
            <flux:card class="dashboard-reveal border-violet-200 bg-violet-50/70 shadow-sm dark:border-violet-400/30 dark:bg-violet-500/10">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <flux:text class="text-xs font-semibold uppercase tracking-wider text-violet-700 dark:text-violet-300">Current Walk-In queue</flux:text>
                        <div class="mt-2 flex flex-wrap items-center gap-3"><flux:heading size="xl" class="font-mono">{{ $currentWalkIn->queue_number }}</flux:heading><x-super-admin.table-cell :value="$currentWalkIn->status" type="status" /></div>
                    </div>
                    <div class="rounded-xl bg-white/80 px-4 py-3 text-center shadow-sm dark:bg-zinc-950/40"><div class="text-xs text-zinc-500">Queue position</div><div class="mt-1 text-2xl font-semibold">#{{ $currentWalkIn->queue_position }}</div></div>
                </div>
                <div class="mt-5 grid gap-4 text-sm sm:grid-cols-3">
                    <div><flux:text class="text-zinc-500">Service</flux:text><div class="mt-1 font-medium">{{ $serviceLabel($currentWalkIn->service_type) }}</div></div>
                    <div><flux:text class="text-zinc-500">Payment</flux:text><div class="mt-1 font-medium">Cash</div></div>
                    <div><flux:text class="text-zinc-500">Location sharing</flux:text><div class="mt-1 font-medium">{{ $currentWalkIn->location_sharing_enabled ? 'Active' : 'Off' }}</div></div>
                </div>
                <div class="mt-5 flex flex-wrap gap-2">
                    <flux:button size="sm" variant="primary" icon="eye" wire:click="openWalkInEntry({{ $currentWalkIn->id }})">View Ticket</flux:button>
                    <flux:button size="sm" variant="subtle" icon="map" wire:click="openWalkInEntry({{ $currentWalkIn->id }})">View Map</flux:button>
                    <flux:button size="sm" variant="danger" icon="x-circle" wire:click="prepareWalkInCancellation({{ $currentWalkIn->id }})">Cancel Walk-In</flux:button>
                </div>
            </flux:card>
        @endif
        <div class="grid gap-6 xl:grid-cols-[minmax(0,1.45fr)_minmax(20rem,0.8fr)]">
            <flux:card class="dashboard-reveal shadow-sm">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <flux:heading size="lg">Current booking</flux:heading>
                        <flux:text class="mt-1 text-zinc-500 dark:text-zinc-400">
                            {{ $currentBooking?->status === 'completed' ? 'This service has been marked completed.' : 'Follow the next step in your home service.' }}
                        </flux:text>
                    </div>
                    @if ($currentBooking)
                        <x-super-admin.table-cell :value="$currentBooking->status" type="status" />
                    @endif
                </div>

                @if ($currentBooking)
                    <div class="mt-6 grid gap-6 lg:grid-cols-[minmax(0,1fr)_minmax(15rem,0.8fr)]">
                        <div class="space-y-5">
                            <div>
                                <flux:text class="text-xs uppercase tracking-wider text-zinc-500">{{ $currentBooking->reference }}</flux:text>
                                <flux:heading size="lg" class="mt-1">{{ $serviceLabel($currentBooking->service_type) }}</flux:heading>
                            </div>
                            <div class="grid gap-4 text-sm sm:grid-cols-2">
                                <div><flux:text class="text-zinc-500">Technician</flux:text><div class="mt-1">{{ $currentBooking->technician?->name ?: 'Matching in progress' }}</div></div>
                                <div><flux:text class="text-zinc-500">Booking type</flux:text><div class="mt-1">{{ \Illuminate\Support\Str::headline($currentBooking->booking_type) }}</div></div>
                                <div class="sm:col-span-2"><flux:text class="text-zinc-500">Service address</flux:text><div class="mt-1">{{ $currentBooking->address }}</div></div>
                                <div class="sm:col-span-2"><flux:text class="text-zinc-500">Schedule</flux:text><div class="mt-1">{{ $formatDateTime($currentBooking->scheduled_at) }}</div></div>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <flux:button size="sm" variant="subtle" icon="eye" wire:click="openBooking({{ $currentBooking->id }})">View booking</flux:button>
                                @if (in_array($currentBooking->status, \App\Models\Booking::ACTIVE_STATUSES, true))
                                    <flux:button size="sm" variant="danger" icon="x-circle" wire:click="prepareCancellation({{ $currentBooking->id }})">Cancel booking</flux:button>
                                @endif
                            </div>
                        </div>

                        <div class="rounded-xl border border-zinc-200 bg-zinc-50 p-4 dark:border-white/10 dark:bg-white/[0.03]">
                            <flux:text class="text-xs uppercase tracking-wider text-zinc-500">Service progress</flux:text>
                            <div class="mt-4 space-y-3 text-sm">
                                @foreach ([
                                    ['Confirmed', 'confirmed', in_array($currentBooking->status, ['pending', 'matching', 'assigned', 'en_route', 'in_progress', 'completed'], true)],
                                    ['Matching', 'matching', in_array($currentBooking->status, ['matching', 'assigned', 'en_route', 'in_progress', 'completed'], true)],
                                    ['On the way', 'on-the-way', in_array($currentBooking->status, ['en_route', 'in_progress', 'completed'], true)],
                                    ['In progress', 'in-progress', in_array($currentBooking->status, ['in_progress', 'completed'], true)],
                                    ['Completed', 'completed', $currentBooking->status === 'completed'],
                                ] as [$step, $stepKey, $done])
                                    <div class="flex items-center gap-3" data-service-progress-step="{{ $stepKey }}" data-complete="{{ $done ? 'true' : 'false' }}">
                                        <span class="flex size-6 items-center justify-center rounded-full {{ $done ? 'bg-emerald-500 text-white' : 'border border-zinc-300 text-zinc-400 dark:border-zinc-600' }}">
                                            @if ($done)<flux:icon name="check" class="size-3.5" />@endif
                                        </span>
                                        <span class="{{ $done ? 'text-zinc-900 dark:text-white' : 'text-zinc-500' }}">{{ $step }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @else
                    <div class="mt-6 rounded-xl border border-dashed border-zinc-300 px-6 py-12 text-center dark:border-zinc-700">
                        <flux:icon name="wrench-screwdriver" class="mx-auto size-8 text-zinc-400" />
                        <flux:heading size="lg" class="mt-4">No active service</flux:heading>
                        <flux:text class="mt-1 text-zinc-500 dark:text-zinc-400">Book a technician when your home needs a hand.</flux:text>
                        <flux:button class="mt-5" size="sm" variant="primary" icon="plus" wire:click="startBooking">Book a service</flux:button>
                    </div>
                @endif
            </flux:card>

            <flux:card class="dashboard-reveal shadow-sm">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <flux:heading size="lg">Next scheduled visit</flux:heading>
                        <flux:text class="mt-1 text-zinc-500 dark:text-zinc-400">Your upcoming appointment at a glance.</flux:text>
                    </div>
                    <x-super-admin.section-menu :actions="[['label' => __('Refresh overview'), 'icon' => 'arrow-path', 'wire' => '$refresh']]" />
                </div>
                @if ($content['upcomingBookings']->isNotEmpty())
                    @php $upcoming = $content['upcomingBookings']->first(); @endphp
                    <div class="mt-6 rounded-xl border border-zinc-200 p-4 dark:border-white/10">
                        <flux:text class="text-xs uppercase tracking-wider text-zinc-500">{{ $formatDate($upcoming->scheduled_at) }}</flux:text>
                        <flux:heading size="lg" class="mt-2">{{ $serviceLabel($upcoming->service_type) }}</flux:heading>
                        <flux:text class="mt-1 text-zinc-500 dark:text-zinc-400">{{ $upcoming->address }}</flux:text>
                        <div class="mt-4 flex items-center justify-between gap-3 text-sm"><span class="text-zinc-500">{{ $formatDateTime($upcoming->scheduled_at) }}</span><x-super-admin.table-cell :value="$upcoming->status" type="status" /></div>
                    </div>
                @else
                    <div class="mt-6 rounded-xl border border-dashed border-zinc-300 px-5 py-10 text-center dark:border-zinc-700">
                        <flux:icon name="calendar-days" class="mx-auto size-8 text-zinc-400" />
                        <flux:text class="mt-3 text-zinc-500">No scheduled visits yet.</flux:text>
                    </div>
                @endif
            </flux:card>
        </div>

        <div class="grid gap-6 xl:grid-cols-[minmax(0,1.25fr)_minmax(18rem,0.75fr)]">
            <div class="dashboard-reveal">
                <div class="flex items-start justify-between gap-3">
                    <div><flux:heading size="lg">Recent bookings</flux:heading><flux:text class="mt-1 text-zinc-500 dark:text-zinc-400">The latest requests moving through FixTrack.</flux:text></div>
                    <div class="flex items-center gap-2"><flux:badge color="zinc" size="sm">{{ $content['recentBookings']->count() }} shown</flux:badge><x-super-admin.section-menu :actions="[['label' => __('View all bookings'), 'icon' => 'arrow-up-right', 'href' => route('customer.module', ['module' => 'my-bookings'])]]" /></div>
                </div>
                <div class="app-table-shell mt-4 overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-white/10 dark:bg-zinc-900" data-app-table-shell>
                    <flux:table class="w-full min-w-[680px]">
                        <flux:table.columns><flux:table.column>Booking</flux:table.column><flux:table.column>Service</flux:table.column><flux:table.column>Schedule</flux:table.column><flux:table.column>Status</flux:table.column><flux:table.column>Payment</flux:table.column><flux:table.column>Actions</flux:table.column></flux:table.columns>
                        <flux:table.rows>
                            @forelse ($content['recentBookings'] as $booking)
                                <flux:table.row :key="'recent-booking-'.$booking->id">
                                    <flux:table.cell variant="strong">{{ $booking->reference }}</flux:table.cell>
                                    <flux:table.cell>{{ $serviceLabel($booking->service_type) }}</flux:table.cell>
                                    <flux:table.cell class="whitespace-nowrap text-zinc-500">{{ $formatDate($booking->scheduled_at ?: $booking->created_at) }}</flux:table.cell>
                                    <flux:table.cell><x-super-admin.table-cell :value="$booking->status" type="status" /></flux:table.cell>
                                    <flux:table.cell><flux:badge color="emerald" size="sm">Cash</flux:badge></flux:table.cell>
                                    <flux:table.cell>
                                        @php $recentBookingActions = [['label' => __('View details'), 'icon' => 'eye', 'wire' => 'openBooking('.$booking->id.')']]; @endphp
                                        @if (in_array($booking->status, \App\Models\Booking::ACTIVE_STATUSES, true))
                                            @php $recentBookingActions[] = ['label' => __('Cancel booking'), 'icon' => 'x-circle', 'wire' => 'prepareCancellation('.$booking->id.')']; @endphp
                                        @endif
                                        <x-table-actions :actions="$recentBookingActions" />
                                    </flux:table.cell>
                                </flux:table.row>
                            @empty
                                <flux:table.row><flux:table.cell colspan="6" class="py-12 text-center text-zinc-500">Your booking history will appear here.</flux:table.cell></flux:table.row>
                            @endforelse
                        </flux:table.rows>
                    </flux:table>
                </div>
            </div>

            <flux:card class="dashboard-reveal shadow-sm">
                <div class="flex items-start justify-between gap-3"><div><flux:heading size="lg">Top 5 Popular Services</flux:heading><flux:text class="mt-1 text-zinc-500 dark:text-zinc-400">The five most-booked services with upfront quotations.</flux:text></div><x-super-admin.section-menu :actions="[['label' => __('Open booking form'), 'icon' => 'plus', 'wire' => 'startBooking']]" /></div>
                <div class="mt-5 space-y-3">
                    @foreach ($content['popularServices'] as $service)
                        <button type="button" wire:key="popular-service-{{ $service->code }}" wire:click="startBooking('{{ $service->code }}')" class="flex w-full items-center justify-between gap-3 rounded-xl border border-zinc-200 p-3 text-start transition hover:border-violet-300 hover:bg-violet-50/60 dark:border-white/10 dark:hover:border-violet-400/40 dark:hover:bg-violet-500/10">
                            <span class="flex items-center gap-3"><span class="flex size-9 items-center justify-center rounded-lg bg-violet-50 text-xs font-semibold text-violet-600 dark:bg-violet-500/10 dark:text-violet-300">{{ strtoupper(substr($service->code, 0, 2)) }}</span><span><span class="block font-medium">{{ $service->name }}</span><span class="block text-xs text-zinc-500">{{ $service->description }}</span></span></span>
                            <flux:icon name="chevron-right" class="size-4 text-zinc-400" />
                        </button>
                    @endforeach
                </div>
            </flux:card>
        </div>
    @elseif ($moduleSlug === 'book-service')
        <div class="mx-auto max-w-4xl">
            <flux:card class="dashboard-reveal shadow-sm">
                <div><flux:heading size="lg">Request a technician</flux:heading><flux:text class="mt-1 text-zinc-500 dark:text-zinc-400">Choose how you want to request service. We will guide you one step at a time.</flux:text></div>
                <div class="mt-6 grid gap-4 sm:grid-cols-2">
                    <button type="button" wire:click="openBookingFlow('quick')" class="group rounded-2xl border border-zinc-200 p-5 text-start transition hover:border-violet-400 hover:bg-violet-50/60 focus:outline-none focus:ring-2 focus:ring-violet-500 focus:ring-offset-2 dark:border-white/10 dark:hover:border-violet-400 dark:hover:bg-violet-500/10 dark:focus:ring-offset-zinc-900">
                        <span class="flex size-11 items-center justify-center rounded-xl bg-violet-100 text-violet-700 transition group-hover:bg-violet-600 group-hover:text-white dark:bg-violet-500/15 dark:text-violet-300"><flux:icon name="bolt" class="size-5" /></span>
                        <span class="mt-4 block text-base font-semibold text-zinc-900 dark:text-white">Quick booking</span>
                        <span class="mt-1 block text-sm text-zinc-500 dark:text-zinc-400">Request the next available technician for your location.</span>
                        <span class="mt-4 flex items-center gap-2 text-sm font-semibold text-violet-700 dark:text-violet-300">Start quick booking <flux:icon name="arrow-right" class="size-4" /></span>
                    </button>
                    <button type="button" wire:click="openBookingFlow('manual')" class="group rounded-2xl border border-zinc-200 p-5 text-start transition hover:border-violet-400 hover:bg-violet-50/60 focus:outline-none focus:ring-2 focus:ring-violet-500 focus:ring-offset-2 dark:border-white/10 dark:hover:border-violet-400 dark:hover:bg-violet-500/10 dark:focus:ring-offset-zinc-900">
                        <span class="flex size-11 items-center justify-center rounded-xl bg-violet-100 text-violet-700 transition group-hover:bg-violet-600 group-hover:text-white dark:bg-violet-500/15 dark:text-violet-300"><flux:icon name="calendar-days" class="size-5" /></span>
                        <span class="mt-4 block text-base font-semibold text-zinc-900 dark:text-white">Manual booking</span>
                        <span class="mt-1 block text-sm text-zinc-500 dark:text-zinc-400">Choose a walk-in visit or arrange a home service.</span>
                        <span class="mt-4 flex items-center gap-2 text-sm font-semibold text-violet-700 dark:text-violet-300">Start manual booking <flux:icon name="arrow-right" class="size-4" /></span>
                    </button>
                </div>
            </flux:card>
        </div>
    @elseif ($moduleSlug === 'my-bookings')
        <div class="dashboard-reveal">
            <div class="mb-4"><flux:heading size="lg">Booking history</flux:heading><flux:text class="mt-1 text-zinc-500 dark:text-zinc-400">Review current requests and completed services.</flux:text></div>
            <div class="flex flex-wrap items-end justify-between gap-4"><div class="flex flex-1 flex-wrap gap-3"><flux:input wire:model.live.debounce.300ms="bookingSearch" placeholder="Search bookings" icon="magnifying-glass" class="w-full sm:w-72" /><flux:select wire:model.live="bookingStatus" placeholder="All statuses" class="w-44"><flux:select.option value="all">All statuses</flux:select.option><flux:select.option value="pending">Pending</flux:select.option><flux:select.option value="matching">Matching</flux:select.option><flux:select.option value="assigned">Assigned</flux:select.option><flux:select.option value="en_route">En route</flux:select.option><flux:select.option value="in_progress">In progress</flux:select.option><flux:select.option value="completed">Completed</flux:select.option><flux:select.option value="cancelled">Cancelled</flux:select.option><flux:select.option value="no_show">No show</flux:select.option></flux:select></div><x-super-admin.section-menu :actions="[['label' => __('Refresh bookings'), 'icon' => 'arrow-path', 'wire' => '$refresh'], ['label' => __('Book a service'), 'icon' => 'plus', 'wire' => 'startBooking']]" /></div>
            <div class="app-table-shell mt-4 overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-white/10 dark:bg-zinc-900" data-app-table-shell><flux:table class="w-full min-w-[880px]"><flux:table.columns><flux:table.column>Booking</flux:table.column><flux:table.column>Service</flux:table.column><flux:table.column>Schedule</flux:table.column><flux:table.column>Address</flux:table.column><flux:table.column>Technician</flux:table.column><flux:table.column>Status</flux:table.column><flux:table.column>Actions</flux:table.column></flux:table.columns><flux:table.rows>@forelse ($content['bookings'] as $booking)<flux:table.row :key="'booking-'.$booking->id"><flux:table.cell variant="strong">{{ $booking->reference }}</flux:table.cell><flux:table.cell>{{ $serviceLabel($booking->service_type) }}</flux:table.cell><flux:table.cell class="whitespace-nowrap text-zinc-500">{{ $formatDateTime($booking->scheduled_at ?: $booking->created_at) }}</flux:table.cell><flux:table.cell class="max-w-xs truncate">{{ $booking->address }}</flux:table.cell><flux:table.cell>{{ $booking->technician?->name ?: 'Unassigned' }}</flux:table.cell><flux:table.cell><x-super-admin.table-cell :value="$booking->status" type="status" /></flux:table.cell><flux:table.cell>@php $bookingActions = [['label' => __('View details'), 'icon' => 'eye', 'wire' => 'openBooking('.$booking->id.')']]; @endphp@if (in_array($booking->status, \App\Models\Booking::ACTIVE_STATUSES, true))@php $bookingActions[] = ['label' => __('Cancel booking'), 'icon' => 'x-circle', 'wire' => 'prepareCancellation('.$booking->id.')']; @endphp@endif<x-table-actions :actions="$bookingActions" /></flux:table.cell></flux:table.row>@empty<flux:table.row><flux:table.cell colspan="7" class="py-14 text-center text-zinc-500">No bookings match your search.</flux:table.cell></flux:table.row>@endforelse</flux:table.rows></flux:table></flux:table><div class="border-t border-zinc-200 px-4 py-3 dark:border-white/10">{{ $content['bookings']->links() }}</div></div>
        </div>
    @elseif ($moduleSlug === 'walk-in-queue')
        <div class="grid gap-6 xl:grid-cols-[minmax(0,0.7fr)_minmax(0,1.3fr)]">
            @if ($content['available'])
                <flux:card class="dashboard-reveal shadow-sm">
                    <div class="flex items-start justify-between gap-3"><div><flux:heading size="lg">Join the queue</flux:heading><flux:text class="mt-1 text-zinc-500 dark:text-zinc-400">Check in for an in-person service visit.</flux:text></div><flux:badge :color="$content['availableSlots'] > 0 ? 'emerald' : 'red'" size="sm">{{ $content['availableSlots'] }} / {{ $content['capacity'] }} slots</flux:badge></div>
                    @if ($content['availableSlots'] === 0)
                        <flux:callout class="mt-5" variant="danger" icon="exclamation-triangle" heading="Walk-In Queue is currently full">Maximum capacity is 3 customers. Please wait until a slot becomes available.</flux:callout>
                    @endif
                    <form class="mt-6 space-y-4" wire:submit="joinWalkInQueue">
                        <flux:select wire:model="walkInServiceType" label="Service type" placeholder="Choose a service">
                            @foreach ($content['services'] as $service)
                                <flux:select.option value="{{ $service->code }}">{{ $service->name }}</flux:select.option>
                            @endforeach
                        </flux:select>
                        <flux:input wire:model="walkInPhone" label="Mobile number" type="tel" required />
                        <flux:textarea wire:model="walkInNotes" label="Notes" rows="4" placeholder="Add anything the service counter should know." />
                        <div class="rounded-xl bg-emerald-50 p-4 text-sm text-emerald-900 dark:bg-emerald-500/10 dark:text-emerald-200"><span class="font-semibold">Payment Method: Cash</span><span class="mt-1 block">Payment will be made in cash at the shop after the service.</span></div>
                        @error('walkInQueue')<flux:callout variant="danger" icon="exclamation-triangle">{{ $message }}</flux:callout>@enderror
                        <div class="flex justify-end"><flux:button type="submit" variant="primary" icon="queue-list" :disabled="$content['availableSlots'] === 0">Join queue</flux:button></div>
                    </form>
                </flux:card>
            @else
                <flux:card class="dashboard-reveal shadow-sm">
                    <div class="rounded-xl border border-dashed border-amber-300 bg-amber-50 px-6 py-12 text-center dark:border-amber-400/30 dark:bg-amber-500/10">
                        <flux:icon name="queue-list" class="mx-auto size-9 text-amber-500" />
                        <flux:heading size="lg" class="mt-4">Walk-in queue is currently unavailable</flux:heading>
                        <flux:text class="mt-1 text-zinc-500 dark:text-zinc-400">{{ $content['message'] }}</flux:text>
                    </div>
                </flux:card>
            @endif
            <div class="dashboard-reveal">
                <div class="flex items-start justify-between gap-3"><div><flux:heading size="lg">My queue entries</flux:heading><flux:text class="mt-1 text-zinc-500 dark:text-zinc-400">Track your check-in and service progress.</flux:text></div><flux:badge color="zinc" size="sm">{{ $content['entries']->total() }} total</flux:badge></div>
                <div class="app-table-shell mt-4 overflow-x-auto rounded-xl border border-zinc-200 bg-white dark:border-white/10 dark:bg-zinc-900" data-app-table-shell><flux:table class="w-full min-w-[760px]"><flux:table.columns><flux:table.column>Queue</flux:table.column><flux:table.column>Position</flux:table.column><flux:table.column>Service</flux:table.column><flux:table.column>Checked in</flux:table.column><flux:table.column>Payment</flux:table.column><flux:table.column>Status</flux:table.column></flux:table.columns><flux:table.rows>@forelse ($content['entries'] as $entry)<flux:table.row :key="'walk-in-'.$entry->id" wire:click="openWalkInEntry({{ $entry->id }})" class="cursor-pointer transition hover:bg-zinc-50 dark:hover:bg-white/[0.03]"><flux:table.cell variant="strong">{{ $entry->queue_number }}</flux:table.cell><flux:table.cell>{{ $entry->queue_position ? '#'.$entry->queue_position : '—' }}</flux:table.cell><flux:table.cell>{{ $serviceLabel($entry->service_type) }}</flux:table.cell><flux:table.cell class="whitespace-nowrap text-zinc-500">{{ $formatDateTime($entry->checked_in_at) }}</flux:table.cell><flux:table.cell>Cash</flux:table.cell><flux:table.cell><x-super-admin.table-cell :value="$entry->status" type="status" /></flux:table.cell></flux:table.row>@empty<flux:table.row><flux:table.cell colspan="6" class="py-14 text-center text-zinc-500">You have no Walk-In queue entries yet.</flux:table.cell></flux:table.row>@endforelse</flux:table.rows></flux:table></div>
                <div class="mt-4">{{ $content['entries']->links() }}</div>
            </div>
        </div>
    @elseif ($moduleSlug === 'quotations')
        <div class="dashboard-reveal">
            <div class="flex items-start justify-between gap-3"><div><flux:heading size="lg">Quotation history</flux:heading><flux:text class="mt-1 text-zinc-500 dark:text-zinc-400">Review technician assessments and approve the proposed work before service continues.</flux:text></div><x-super-admin.section-menu :actions="[['label' => __('View bookings'), 'icon' => 'clipboard-document-list', 'href' => route('customer.module', ['module' => 'my-bookings'])]]" /></div>
            @if ($content['available'])
                <div class="app-table-shell mt-4 overflow-x-auto rounded-xl border border-zinc-200 bg-white dark:border-white/10 dark:bg-zinc-900" data-app-table-shell><flux:table class="w-full min-w-[940px]"><flux:table.columns><flux:table.column>Booking</flux:table.column><flux:table.column>Service</flux:table.column><flux:table.column>Technician</flux:table.column><flux:table.column>Assessment</flux:table.column><flux:table.column>Total</flux:table.column><flux:table.column>Status</flux:table.column><flux:table.column>Actions</flux:table.column></flux:table.columns><flux:table.rows>@forelse ($content['quotations'] as $quotation)<flux:table.row :key="'quotation-'.$quotation->id"><flux:table.cell variant="strong">{{ $quotation->booking?->reference ?: '—' }}</flux:table.cell><flux:table.cell>{{ $quotation->booking?->service?->name ?: \Illuminate\Support\Str::headline($quotation->booking?->service_type) }}</flux:table.cell><flux:table.cell>{{ $quotation->technician?->name ?: '—' }}</flux:table.cell><flux:table.cell class="max-w-xs whitespace-normal text-zinc-500">{{ $quotation->assessment_notes }}</flux:table.cell><flux:table.cell class="whitespace-nowrap tabular-nums">₱{{ number_format((float) $quotation->total_amount, 2) }}</flux:table.cell><flux:table.cell><x-super-admin.table-cell :value="$quotation->status" type="status" /></flux:table.cell><flux:table.cell>@if ($quotation->status === 'awaiting_approval')<div class="flex gap-2"><flux:button size="sm" variant="primary" wire:click="respondToQuotation({{ $quotation->id }}, 'approved')">Approve</flux:button><flux:button size="sm" variant="subtle" wire:click="respondToQuotation({{ $quotation->id }}, 'rejected')">Reject</flux:button></div>@else<flux:text class="text-xs text-zinc-500">Answered</flux:text>@endif</flux:table.cell></flux:table.row>@empty<flux:table.row><flux:table.cell colspan="7" class="py-14 text-center text-zinc-500">No quotations to review yet. They will appear after a technician assessment.</flux:table.cell></flux:table.row>@endforelse</flux:table.rows></flux:table></flux:table><div class="border-t border-zinc-200 px-4 py-3 dark:border-white/10">{{ $content['quotations']->links() }}</div></div>
            @else
                <div class="mt-8 rounded-xl border border-dashed border-zinc-300 px-6 py-14 text-center dark:border-zinc-700"><flux:icon name="document-text" class="mx-auto size-9 text-zinc-400" /><flux:heading size="lg" class="mt-4">No quotations to review</flux:heading><flux:text class="mx-auto mt-1 max-w-lg text-zinc-500">{{ $content['message'] }}</flux:text></div>
            @endif
        </div>
    @elseif ($moduleSlug === 'payments')
        <div class="dashboard-reveal">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <flux:heading size="lg">Payment history</flux:heading>
                    <flux:text class="mt-1 text-zinc-500 dark:text-zinc-400">All services are paid in cash. Track each payment state and receipt here.</flux:text>
                </div>
                <x-super-admin.section-menu :actions="[['label' => __('Refresh payments'), 'icon' => 'arrow-path', 'wire' => '$refresh']]" />
            </div>

            <div class="app-table-shell mt-4 overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-white/10 dark:bg-zinc-900" data-app-table-shell>
                <flux:table class="w-full min-w-[880px]">
                    <flux:table.columns>
                        <flux:table.column>Booking</flux:table.column>
                        <flux:table.column>Service</flux:table.column>
                        <flux:table.column>Amount</flux:table.column>
                        <flux:table.column>Method</flux:table.column>
                        <flux:table.column>Paid at</flux:table.column>
                        <flux:table.column>Status</flux:table.column>
                        <flux:table.column align="end">Actions</flux:table.column>
                    </flux:table.columns>
                    <flux:table.rows>
                        @forelse ($content['payments'] as $payment)
                            <flux:table.row :key="'payment-'.$payment->id">
                                <flux:table.cell variant="strong">{{ $payment->booking?->reference ?: '—' }}</flux:table.cell>
                                <flux:table.cell>{{ $serviceLabel($payment->booking?->service_type) }}</flux:table.cell>
                                <flux:table.cell class="tabular-nums">₱{{ number_format((float) $payment->amount, 2) }}</flux:table.cell>
                                <flux:table.cell>{{ $paymentMethodLabel($payment->method) }}</flux:table.cell>
                                <flux:table.cell class="whitespace-nowrap text-zinc-500">{{ $formatDateTime($payment->paid_at ?: $payment->created_at) }}</flux:table.cell>
                                <flux:table.cell><x-super-admin.table-cell :value="$payment->status" type="status" /></flux:table.cell>
                                <flux:table.cell align="end">
                                    <flux:button size="sm" variant="subtle" icon="eye" wire:click="openPayment({{ $payment->id }})">View details</flux:button>
                                </flux:table.cell>
                            </flux:table.row>
                        @empty
                            <flux:table.row>
                                <flux:table.cell colspan="7" class="py-14 text-center text-zinc-500">Payment records will appear here after a booking is billed.</flux:table.cell>
                            </flux:table.row>
                        @endforelse
                    </flux:table.rows>
                </flux:table>
                <div class="border-t border-zinc-200 px-4 py-3 dark:border-white/10">{{ $content['payments']->links() }}</div>
            </div>
        </div>
    @elseif ($moduleSlug === 'ratings-reviews')
        <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_minmax(19rem,0.7fr)]"><div class="dashboard-reveal"><div class="flex items-start justify-between gap-3"><div><flux:heading size="lg">Your reviews</flux:heading><flux:text class="mt-1 text-zinc-500 dark:text-zinc-400">Feedback helps technicians and the FixTrack service improve.</flux:text></div><x-super-admin.section-menu :actions="[['label' => __('Refresh reviews'), 'icon' => 'arrow-path', 'wire' => '$refresh']]" /></div><div class="app-table-shell mt-4 overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-white/10 dark:bg-zinc-900" data-app-table-shell><flux:table class="w-full min-w-[720px]"><flux:table.columns><flux:table.column>Booking</flux:table.column><flux:table.column>Technician</flux:table.column><flux:table.column>Rating</flux:table.column><flux:table.column>Review</flux:table.column><flux:table.column>Status</flux:table.column></flux:table.columns><flux:table.rows>@forelse ($content['reviews'] as $review)<flux:table.row :key="'review-'.$review->id"><flux:table.cell variant="strong">{{ $review->booking?->reference ?: '—' }}</flux:table.cell><flux:table.cell>{{ $review->technician?->name ?: '—' }}</flux:table.cell><flux:table.cell><flux:badge color="amber" size="sm">★ {{ $review->rating }} / 5</flux:badge></flux:table.cell><flux:table.cell class="max-w-sm truncate">{{ $review->comment ?: 'No written feedback.' }}</flux:table.cell><flux:table.cell><x-super-admin.table-cell :value="$review->status" type="status" /></flux:table.cell></flux:table.row>@empty<flux:table.row><flux:table.cell colspan="5" class="py-14 text-center text-zinc-500">No reviews submitted yet.</flux:table.cell></flux:table.row>@endforelse</flux:table.rows></flux:table></flux:table><div class="border-t border-zinc-200 px-4 py-3 dark:border-white/10">{{ $content['reviews']->links() }}</div></div></div><flux:card class="dashboard-reveal shadow-sm"><div class="flex items-start justify-between gap-3"><div><flux:heading size="lg">Leave feedback</flux:heading><flux:text class="mt-1 text-zinc-500 dark:text-zinc-400">Review completed services when you are ready.</flux:text></div><flux:icon name="star" class="size-5 text-amber-500" /></div><div class="mt-5 space-y-3">@forelse ($content['reviewableBookings'] as $booking)<div class="rounded-xl border border-zinc-200 p-3 dark:border-white/10"><div class="flex items-center justify-between gap-3"><div><div class="font-medium">{{ $serviceLabel($booking->service_type) }}</div><flux:text class="text-xs text-zinc-500">{{ $booking->reference }}</flux:text></div><flux:button size="sm" variant="primary" wire:click="startReview({{ $booking->id }})">Review</flux:button></div></div>@empty<flux:text class="text-sm text-zinc-500">Completed bookings ready for feedback will appear here.</flux:text>@endforelse</div>@if ($reviewBookingId)<form class="mt-5 space-y-4 border-t border-zinc-200 pt-5 dark:border-white/10" wire:submit="createReview"><flux:select wire:model="reviewRating" label="Rating" placeholder="Choose a rating"><flux:select.option value="1">1 / 5</flux:select.option><flux:select.option value="2">2 / 5</flux:select.option><flux:select.option value="3">3 / 5</flux:select.option><flux:select.option value="4">4 / 5</flux:select.option><flux:select.option value="5">5 / 5</flux:select.option></flux:select><flux:textarea wire:model="reviewComment" label="Comment" rows="3" placeholder="Tell us about the service." /><div class="flex justify-end"><flux:button type="submit" variant="primary">Submit review</flux:button></div></form>@endif</flux:card></div>
    @elseif ($moduleSlug === 'notifications')
        <div class="dashboard-reveal"><div class="flex items-start justify-between gap-3"><div><flux:heading size="lg">Latest updates</flux:heading><flux:text class="mt-1 text-zinc-500 dark:text-zinc-400">Booking and support activity collected for your account.</flux:text></div><x-super-admin.section-menu :actions="[['label' => __('Refresh updates'), 'icon' => 'arrow-path', 'wire' => '$refresh']]" /></div><div class="mt-4 grid gap-3">@forelse ($content['items'] as $item)<div class="flex items-start gap-4 rounded-xl border border-zinc-200 bg-white p-4 dark:border-white/10 dark:bg-zinc-900"><div class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-violet-50 text-violet-600 dark:bg-violet-500/10 dark:text-violet-300"><flux:icon name="bell" class="size-5" /></div><div class="min-w-0 flex-1"><div class="flex flex-wrap items-center justify-between gap-2"><flux:heading size="sm">{{ $item['title'] }}</flux:heading><x-super-admin.table-cell :value="$item['status']" type="status" /></div><flux:text class="mt-1 text-zinc-500 dark:text-zinc-400">{{ $item['detail'] }}</flux:text><flux:text class="mt-2 text-xs text-zinc-400">{{ $formatDateTime($item['date']) }}</flux:text></div></div>@empty<div class="rounded-xl border border-dashed border-zinc-300 px-6 py-14 text-center dark:border-zinc-700"><flux:icon name="bell" class="mx-auto size-8 text-zinc-400" /><flux:heading size="lg" class="mt-4">No new updates</flux:heading><flux:text class="mt-1 text-zinc-500">You are all caught up.</flux:text></div>@endforelse</div></div>
    @elseif ($moduleSlug === 'support')
        <div class="grid gap-6 xl:grid-cols-[minmax(0,0.7fr)_minmax(0,1.3fr)]"><flux:card class="dashboard-reveal shadow-sm"><div class="flex items-start justify-between gap-3"><div><flux:heading size="lg">Contact support</flux:heading><flux:text class="mt-1 text-zinc-500 dark:text-zinc-400">Send a request and keep the conversation tied to your account.</flux:text></div><flux:icon name="chat-bubble-left-right" class="size-5 text-violet-500" /></div><form class="mt-6 space-y-4" wire:submit="createSupportTicket"><flux:input wire:model="supportSubject" label="Subject" required /><div class="grid gap-4 sm:grid-cols-2"><flux:select wire:model="supportCategory" label="Category"><flux:select.option value="booking">Booking</flux:select.option><flux:select.option value="payment">Payment</flux:select.option><flux:select.option value="technical">Technical</flux:select.option><flux:select.option value="account">Account</flux:select.option><flux:select.option value="other">Other</flux:select.option></flux:select><flux:select wire:model="supportPriority" label="Priority"><flux:select.option value="low">Low</flux:select.option><flux:select.option value="normal">Normal</flux:select.option><flux:select.option value="high">High</flux:select.option></flux:select></div><flux:textarea wire:model="supportMessage" label="Message" rows="5" required /><div class="flex justify-end"><flux:button type="submit" variant="primary" icon="paper-airplane">Send request</flux:button></div></form></flux:card><div class="dashboard-reveal"><div class="flex items-start justify-between gap-3"><div><flux:heading size="lg">Your support requests</flux:heading><flux:text class="mt-1 text-zinc-500 dark:text-zinc-400">Track questions about bookings, payments, or your account.</flux:text></div><x-super-admin.section-menu :actions="[['label' => __('Refresh requests'), 'icon' => 'arrow-path', 'wire' => '$refresh']]" /></div><div class="app-table-shell mt-4 overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-white/10 dark:bg-zinc-900" data-app-table-shell><flux:table class="w-full min-w-[720px]"><flux:table.columns><flux:table.column>Reference</flux:table.column><flux:table.column>Subject</flux:table.column><flux:table.column>Category</flux:table.column><flux:table.column>Priority</flux:table.column><flux:table.column>Updated</flux:table.column><flux:table.column>Status</flux:table.column></flux:table.columns><flux:table.rows>@forelse ($content['tickets'] as $ticket)<flux:table.row :key="'ticket-'.$ticket->id"><flux:table.cell variant="strong">{{ $ticket->reference }}</flux:table.cell><flux:table.cell>{{ $ticket->subject }}</flux:table.cell><flux:table.cell><x-super-admin.table-cell :value="$ticket->category" type="category" /></flux:table.cell><flux:table.cell><x-super-admin.table-cell :value="$ticket->priority" type="priority" /></flux:table.cell><flux:table.cell class="whitespace-nowrap text-zinc-500">{{ $formatDateTime($ticket->updated_at) }}</flux:table.cell><flux:table.cell><x-super-admin.table-cell :value="$ticket->status" type="status" /></flux:table.cell></flux:table.row>@empty<flux:table.row><flux:table.cell colspan="6" class="py-14 text-center text-zinc-500">No support requests yet.</flux:table.cell></flux:table.row>@endforelse</flux:table.rows></flux:table></div><div class="mt-4">{{ $content['tickets']->links() }}</div></div></div>
    @elseif ($moduleSlug === 'settings')
        <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_minmax(18rem,0.7fr)]"><flux:card class="dashboard-reveal shadow-sm"><div class="flex items-start gap-4"><div class="flex size-12 items-center justify-center rounded-xl bg-zinc-100 text-zinc-700 dark:bg-white/10 dark:text-zinc-200">{{ $content['user']->initials() }}</div><div><flux:heading size="lg">{{ $content['user']->name }}</flux:heading><flux:text class="mt-1 text-zinc-500">{{ $content['user']->email }}</flux:text><div class="mt-3"><x-super-admin.table-cell :value="$content['user']->email_verified_at ? 'Verified' : 'Pending'" type="status" /></div></div></div><div class="mt-8 grid gap-3 sm:grid-cols-3"><flux:button variant="primary" href="{{ route('profile.edit') }}" wire:navigate>Profile</flux:button><flux:button variant="subtle" href="{{ route('security.edit') }}" wire:navigate>Security</flux:button><flux:button variant="subtle" href="{{ route('appearance.edit') }}" wire:navigate>Appearance</flux:button></div></flux:card><flux:card class="dashboard-reveal shadow-sm"><flux:heading size="lg">Account preferences</flux:heading><flux:text class="mt-1 text-zinc-500 dark:text-zinc-400">Use the settings pages to update your profile and sign-in protection.</flux:text><div class="mt-6 space-y-3 text-sm"><div class="flex items-center justify-between gap-3 border-b border-zinc-200 pb-3 dark:border-white/10"><span class="text-zinc-500">Role</span><x-super-admin.table-cell :value="ucfirst($content['user']->role)" type="role" /></div><div class="flex items-center justify-between gap-3"><span class="text-zinc-500">Member since</span><span>{{ $formatDate($content['user']->created_at) }}</span></div></div></flux:card></div>
    @endif

    </div>

    @php
        $isWalkInBooking = $bookingFlow === 'manual' && $manualServiceMode === 'walk-in';
        $bookingTotalSteps = $bookingFlow === 'manual' ? ($isWalkInBooking ? 5 : 3) : 2;
        $isWalkInTicketStep = $isWalkInBooking && $bookingStep === 5;
        $isBookingFinalStep = $isWalkInBooking ? $bookingStep === 4 : $bookingStep === $bookingTotalSteps;
        $isWalkInCategoryStep = $isWalkInBooking && $bookingStep === 2;
        $isWalkInShopStep = $isWalkInBooking && $bookingStep === 3;
        $isServiceDetailsStep = ($bookingFlow === 'quick' && $bookingStep === 1) || ($bookingFlow === 'manual' && $manualServiceMode === 'home-service' && $bookingStep === 2);
        $showsBookingProgress = $bookingFlow !== 'manual' || $bookingStep > 1;
        $bookingModalTitle = match (true) {
            $bookingFlow === 'manual' && $bookingStep > 1 && $manualServiceMode === 'walk-in' => 'Walk-in booking',
            $bookingFlow === 'manual' && $bookingStep > 1 && $manualServiceMode === 'home-service' => 'Home service booking',
            default => $bookingFlow === 'manual' ? 'Manual booking' : 'Quick booking',
        };
    @endphp
    <flux:modal name="customer-booking-flow" scroll="none" class="max-w-3xl {{ $isServiceDetailsStep ? 'overflow-hidden pb-2!' : '' }}" @close="closeBookingFlow" wire:model="showBookingFlow">
        <form class="{{ $isServiceDetailsStep ? 'space-y-4 overflow-hidden' : 'space-y-6' }}" wire:submit="submitBookingFlow">
            <div class="{{ $showsBookingProgress ? 'space-y-4' : '' }}">
                <div class="pe-12" data-booking-modal-header>
                    <div>
                        <flux:heading size="lg">{{ $bookingModalTitle }}</flux:heading>
                        @if ($showsBookingProgress)
                            <div class="mt-1 flex flex-wrap items-center gap-2" data-booking-progress-summary>
                                <flux:text class="text-zinc-500 dark:text-zinc-400">Step {{ $bookingStep }} of {{ $bookingTotalSteps }}</flux:text>
                                <flux:badge color="violet" size="sm" class="shrink-0" data-booking-step-count>{{ $bookingStep }}/{{ $bookingTotalSteps }}</flux:badge>
                            </div>
                        @endif
                    </div>
                </div>
                @if ($showsBookingProgress)
                    <div class="flex gap-2" aria-label="Booking progress">
                        @for ($step = 1; $step <= $bookingTotalSteps; $step++)
                            <span class="h-1.5 min-w-0 flex-1 rounded-full {{ $step <= $bookingStep ? 'bg-violet-600' : 'bg-zinc-200 dark:bg-zinc-700' }}"></span>
                        @endfor
                    </div>
                @endif
            </div>

            @if ($bookingFlow === 'manual' && $bookingStep === 1)
                <div wire:key="booking-flow-manual-service-mode-step">
                    <flux:heading size="md">How would you like to receive the service?</flux:heading>
                    <flux:text class="mt-1 text-zinc-500 dark:text-zinc-400">Choose one option to continue.</flux:text>
                    <div class="mt-4 grid gap-3 sm:grid-cols-2">
                        <div>
                            <input id="manual-service-mode-walk-in" type="radio" name="manual-service-mode" value="walk-in" wire:model.live="manualServiceMode" class="peer sr-only" />
                            <label for="manual-service-mode-walk-in" class="block h-full min-h-28 cursor-pointer rounded-xl border border-zinc-200 p-4 text-start transition hover:border-violet-300 peer-checked:border-violet-500 peer-checked:bg-violet-50 peer-checked:ring-2 peer-checked:ring-violet-200 dark:border-white/10 dark:hover:border-violet-400/40 dark:peer-checked:border-violet-400 dark:peer-checked:bg-violet-500/10 dark:peer-checked:ring-violet-500/20">
                                <span class="flex items-start gap-3"><span class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-zinc-100 text-zinc-700 dark:bg-white/10 dark:text-zinc-200"><flux:icon name="building-storefront" class="size-5" /></span><span class="min-w-0"><span class="block font-semibold text-zinc-900 dark:text-white">Walk-in</span><span class="mt-0.5 block text-sm leading-5 text-zinc-500 dark:text-zinc-400">Visit a service location and join the queue.</span></span></span>
                            </label>
                        </div>
                        <div>
                            <input id="manual-service-mode-home" type="radio" name="manual-service-mode" value="home-service" wire:model.live="manualServiceMode" class="peer sr-only" />
                            <label for="manual-service-mode-home" class="block h-full min-h-28 cursor-pointer rounded-xl border border-zinc-200 p-4 text-start transition hover:border-violet-300 peer-checked:border-violet-500 peer-checked:bg-violet-50 peer-checked:ring-2 peer-checked:ring-violet-200 dark:border-white/10 dark:hover:border-violet-400/40 dark:peer-checked:border-violet-400 dark:peer-checked:bg-violet-500/10 dark:peer-checked:ring-violet-500/20">
                                <span class="flex items-start gap-3"><span class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-zinc-100 text-zinc-700 dark:bg-white/10 dark:text-zinc-200"><flux:icon name="map-pin" class="size-5" /></span><span class="min-w-0"><span class="block font-semibold text-zinc-900 dark:text-white">Home service</span><span class="mt-0.5 block text-sm leading-5 text-zinc-500 dark:text-zinc-400">Schedule a technician to visit your address.</span></span></span>
                            </label>
                        </div>
                    </div>
                    @error('manualServiceMode')<p class="mt-2 text-sm font-medium text-red-600">{{ $message }}</p>@enderror
                </div>
            @elseif ($isWalkInCategoryStep)
                <div wire:key="booking-flow-walk-in-category-step">
                    <flux:heading size="md">Choose a service category</flux:heading>
                    <flux:text class="mt-1 text-zinc-500 dark:text-zinc-400">Choose the type of service you need before selecting a walk-in shop.</flux:text>
                    <div class="mt-4 max-h-64 overflow-y-auto overscroll-contain pr-2" aria-label="Walk-in service category selector">
                        <div class="grid gap-3 sm:grid-cols-2">
                            @foreach ($content['services']->groupBy('category') as $category => $services)
                                <button type="button" wire:key="booking-flow-walk-in-category-{{ $category }}" wire:click="chooseServiceCategory(@js($category))" class="min-h-20 rounded-xl border p-4 text-start transition hover:border-violet-300 hover:bg-violet-50/50 dark:border-white/10 dark:hover:border-violet-400/40 dark:hover:bg-violet-500/10 {{ $serviceCategory === $category ? 'border-violet-500 bg-violet-50 ring-2 ring-violet-200 dark:border-violet-400 dark:bg-violet-500/10 dark:ring-violet-500/20' : 'border-zinc-200' }}">
                                    <span class="block font-semibold text-zinc-900 dark:text-white">{{ $category }}</span>
                                    <span class="mt-1 block text-sm leading-5 text-zinc-500 dark:text-zinc-400">{{ $walkInCategorySummaries[$category] ?? 'Professional services for your selected category.' }}</span>
                                </button>
                            @endforeach
                        </div>
                    </div>
                    @error('serviceCategory')<p class="mt-2 text-sm font-medium text-red-600">{{ $message }}</p>@enderror
                </div>
            @elseif ($isWalkInShopStep)
                <div wire:key="booking-flow-walk-in-shop-step">
                    <flux:heading size="md">Choose a walk-in shop</flux:heading>
                    <flux:text class="mt-1 text-zinc-500 dark:text-zinc-400">Choose a verified shop that provides {{ $serviceCategory }} services.</flux:text>
                    <div class="mt-4 grid gap-3 sm:grid-cols-[minmax(0,1fr)_10rem]">
                        <flux:field>
                            <flux:label for="walk-in-shop-address">Your location</flux:label>
                            <div class="flex h-10 overflow-hidden rounded-lg border bg-white shadow-xs transition focus-within:ring-2 focus-within:ring-violet-500/40 dark:bg-white/10 {{ $errors->has('walkInShopAddress') ? 'border-red-500 dark:border-red-500' : 'border-zinc-200 border-b-zinc-300/80 dark:border-white/10' }}">
                                <input id="walk-in-shop-address" type="text" wire:model.live.debounce.500ms="walkInShopAddress" placeholder="Enter your address or city" class="min-w-0 flex-1 border-0 bg-transparent px-3 py-2 text-base leading-[1.375rem] text-zinc-700 outline-none placeholder:text-zinc-400 sm:text-sm dark:text-zinc-300 dark:placeholder:text-zinc-400" />
                                <button type="button" data-app-walk-in-locate class="inline-flex size-10 shrink-0 items-center justify-center border-s border-zinc-200 text-zinc-500 transition hover:bg-violet-50 hover:text-violet-700 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-violet-500 dark:border-white/10 dark:text-zinc-400 dark:hover:bg-violet-500/10 dark:hover:text-violet-300" aria-label="Use my current location" title="Use my current location">
                                    <flux:icon name="map-pin" class="size-5" />
                                </button>
                            </div>
                            <flux:error name="walkInShopAddress" />
                        </flux:field>
                        <flux:select wire:model.live="walkInShopSort" label="Sort shops by">
                            <flux:select.option value="ratings">Ratings</flux:select.option>
                            <flux:select.option value="nearest">Nearest</flux:select.option>
                            <flux:select.option value="recommended">Recommended</flux:select.option>
                        </flux:select>
                    </div>
                    <p data-app-walk-in-location-status class="mt-2 hidden text-sm text-zinc-500 dark:text-zinc-400" role="status" aria-live="polite"></p>
                    @if ($walkInShopAddressSuggestions !== [])
                        <div class="mt-3 overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-white/10 dark:bg-zinc-900">
                            <p class="border-b border-zinc-200 px-3 py-2 text-sm font-medium text-zinc-700 dark:border-white/10 dark:text-zinc-200">Select your location</p>
                            <div class="max-h-48 overflow-y-auto">
                                @foreach ($walkInShopAddressSuggestions as $index => $suggestion)
                                    <button type="button" wire:key="booking-flow-walk-in-location-{{ $index }}" wire:click="selectWalkInShopAddress({{ $index }})" class="block w-full border-b border-zinc-100 px-3 py-3 text-start text-sm text-zinc-600 transition last:border-b-0 hover:bg-violet-50 hover:text-zinc-900 dark:border-white/5 dark:text-zinc-300 dark:hover:bg-violet-500/10 dark:hover:text-white">
                                        {{ $suggestion['label'] }}
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    @endif
                    @if (trim($walkInShopAddress) !== '')
                        <flux:text class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">Showing shops in or near {{ $walkInShopAddress }}. Results are ordered by proximity, then highest rating.</flux:text>
                    @endif
                    <div class="mt-4 overscroll-contain pr-2" style="max-height: 20rem; overflow-y: auto;" aria-label="Available walk-in shops">
                        <div class="grid gap-3 sm:grid-cols-2">
                            @forelse ($walkInShops as $walkInShop)
                                <button type="button" wire:key="booking-flow-walk-in-shop-{{ $walkInShop->id }}" wire:click="selectWalkInShop({{ $walkInShop->id }})" class="rounded-xl border p-4 text-start transition hover:border-violet-300 hover:bg-violet-50/50 dark:border-white/10 dark:hover:border-violet-400/40 dark:hover:bg-violet-500/10 {{ $selectedWalkInShopId === $walkInShop->id ? 'border-violet-500 bg-violet-50 ring-2 ring-violet-200 dark:border-violet-400 dark:bg-violet-500/10 dark:ring-violet-500/20' : 'border-zinc-200' }}">
                                    <span class="flex items-start gap-3"><span class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-violet-100 text-violet-700 dark:bg-violet-500/15 dark:text-violet-300"><flux:icon name="building-storefront" class="size-5" /></span><span class="min-w-0"><span class="block font-semibold text-zinc-900 dark:text-white">{{ $walkInShop->name }} Service Store</span><span class="mt-1 flex items-center gap-1 text-sm font-medium text-amber-600 dark:text-amber-300"><flux:icon name="star" class="size-4" />{{ number_format((float) $walkInShop->walk_in_rating, 1) }} rating</span><span class="mt-1 block text-sm text-zinc-500 dark:text-zinc-400">{{ $walkInShop->technicianVerification?->address }}</span>@if ($walkInShop->walk_in_distance !== null)<span class="mt-1 block text-xs text-zinc-500 dark:text-zinc-400">{{ number_format((float) $walkInShop->walk_in_distance, 1) }} km away</span>@else<span class="mt-1 block text-xs text-zinc-400 dark:text-zinc-500">Set your location to see the distance</span>@endif</span></span>
                                </button>
                            @empty
                                <div class="sm:col-span-2 rounded-xl border border-dashed border-zinc-300 px-5 py-10 text-center dark:border-zinc-700"><flux:icon name="building-storefront" class="mx-auto size-8 text-zinc-400" /><flux:heading size="md" class="mt-3">No walk-in shops found</flux:heading><flux:text class="mt-1 text-sm text-zinc-500">Try another service category.</flux:text></div>
                            @endforelse
                        </div>
                    </div>
                    @error('walkInShopId')<p class="mt-2 text-sm font-medium text-red-600">{{ $message }}</p>@enderror
                </div>
            @elseif ($isServiceDetailsStep)
                <div wire:key="booking-flow-service-details-step">
                    <flux:heading size="md">Choose a service category</flux:heading>
                    <flux:text class="mt-1 text-zinc-500 dark:text-zinc-400">Select a category, then choose the exact service.</flux:text>
                    <div class="mt-4 max-h-56 overflow-y-auto overscroll-contain pr-2 sm:max-h-64" aria-label="Service category and service selector">
                        <div class="{{ $serviceCategory === '' ? 'grid gap-3 sm:grid-cols-2' : 'space-y-3' }}">
                            <input id="booking-flow-category-reset" type="radio" name="booking-flow-category" value="" wire:model="serviceCategory" class="sr-only" />
                            <label for="booking-flow-category-reset" wire:show="serviceCategory !== ''" class="col-span-full inline-flex w-fit cursor-pointer items-center gap-1.5 text-sm font-semibold text-violet-700 transition hover:text-violet-900 dark:text-violet-300 dark:hover:text-violet-100"><flux:icon name="arrow-left" class="size-4" />Back to categories</label>
                            @foreach ($content['services']->groupBy('category') as $category => $services)
                                @php
                                    $serviceNames = $services->pluck('name');
                                    $servicePreview = $serviceNames->take(2)->join(', ');
                                    $remainingServiceCount = $serviceNames->count() - 2;
                                @endphp
                                <div wire:key="booking-flow-category-{{ $category }}" wire:show="serviceCategory === '' || serviceCategory === @js($category)">
                                    <input id="booking-flow-category-{{ $loop->index }}" type="radio" name="booking-flow-category" value="{{ $category }}" wire:model="serviceCategory" class="peer sr-only" />
                                    <label for="booking-flow-category-{{ $loop->index }}" class="block min-h-20 cursor-pointer rounded-xl border border-zinc-200 p-4 text-start transition hover:border-violet-300 hover:bg-violet-50/50 peer-checked:border-violet-500 peer-checked:bg-violet-50 peer-checked:ring-2 peer-checked:ring-violet-200 dark:border-white/10 dark:hover:border-violet-400/40 dark:hover:bg-violet-500/10 dark:peer-checked:border-violet-400 dark:peer-checked:bg-violet-500/10 dark:peer-checked:ring-violet-500/20">
                                        <span class="block font-semibold text-zinc-900 dark:text-white">{{ $category }}</span>
                                        <span class="mt-1 block text-sm text-zinc-500 peer-checked:hidden">{{ $servicePreview }}{{ $remainingServiceCount > 0 ? ' + '.$remainingServiceCount.' more services' : '' }}</span>
                                    </label>
                                    <div class="mt-3 hidden rounded-xl border border-violet-200 bg-violet-50/70 p-4 peer-checked:block dark:border-violet-400/30 dark:bg-violet-500/10">
                                        <span class="block text-sm font-semibold text-zinc-900 dark:text-white">Choose a service</span>
                                        <div class="mt-3 grid gap-2 sm:grid-cols-2">
                                            @foreach ($services as $service)
                                                <div wire:key="booking-flow-service-{{ $service->code }}">
                                                    <input id="booking-flow-service-{{ $service->code }}" type="radio" name="booking-flow-service" value="{{ $service->code }}" wire:model="serviceType" class="peer sr-only" />
                                                    <label for="booking-flow-service-{{ $service->code }}" class="block min-h-16 cursor-pointer rounded-xl border border-zinc-200 bg-white p-3 text-start transition hover:border-violet-300 peer-checked:border-violet-500 peer-checked:ring-2 peer-checked:ring-violet-200 dark:border-white/10 dark:bg-zinc-900 dark:hover:border-violet-400/40 dark:peer-checked:border-violet-400 dark:peer-checked:ring-violet-500/20">
                                                        <span class="block font-medium text-zinc-900 dark:text-white">{{ $service->name }}</span>
                                                        <span class="mt-1 block text-xs text-zinc-500">{{ $service->description }}</span>
                                                    </label>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    @error('serviceCategory')<p class="mt-2 text-sm font-medium text-red-600">{{ $message }}</p>@enderror
                    @error('serviceType')<p class="mt-2 text-sm font-medium text-red-600">{{ $message }}</p>@enderror
                    <div class="mt-3 rounded-xl border border-zinc-200 bg-zinc-50/70 p-2.5 dark:border-white/10 dark:bg-white/[0.03]">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <label for="booking-description" class="text-xs font-semibold text-zinc-900 dark:text-white">What needs attention? <span class="text-red-600">*</span></label>
                                <p class="mt-0.5 text-xs leading-4 text-zinc-500 dark:text-zinc-400">These details will be shared with the technician so they can prepare.</p>
                            </div>
                            <span class="shrink-0 rounded-full bg-violet-100 px-2 py-0.5 text-[11px] font-medium text-violet-700 dark:bg-violet-500/15 dark:text-violet-300">Required</span>
                        </div>
                        <flux:textarea id="booking-description" wire:model="description" aria-label="What needs attention?" class="mt-2" placeholder="Describe the issue, symptoms, or anything the technician should bring." rows="2" required />
                    </div>
                </div>
            @elseif ($isWalkInTicketStep)
                <div wire:key="booking-flow-walk-in-ticket-step" class="py-2 text-center">
                    @if ($walkInTicket)
                        <flux:icon name="ticket" class="mx-auto size-10 text-violet-600 dark:text-violet-300" />
                        <div class="mt-6 rounded-2xl border border-violet-200 bg-violet-50/70 p-5 dark:border-violet-400/30 dark:bg-violet-500/10" data-walk-in-ticket-card>
                            <flux:heading size="lg">{{ $walkInTicket->technician?->name }} Service Store</flux:heading>
                            <flux:text class="mt-1 text-zinc-500 dark:text-zinc-400">Your walk-in queue ticket has been issued.</flux:text>
                            <div class="mt-6 text-sm font-medium text-zinc-600 dark:text-zinc-300">Your Queue Number Is</div>
                            <div class="mt-2 text-4xl font-bold tracking-tight text-violet-700 dark:text-violet-300">{{ $walkInTicket->queue_number }}</div>
                            <div class="mt-6 space-y-3 text-sm text-zinc-600 dark:text-zinc-300">
                                <div><span class="block text-xs font-semibold uppercase tracking-wide text-zinc-500">Queue Position</span><span>{{ $walkInTicket->queue_position ? '#'.$walkInTicket->queue_position.' of '.$walkInTicket::MAX_ACTIVE : 'No longer in the active queue' }}</span></div>
                                <div><span class="block text-xs font-semibold uppercase tracking-wide text-zinc-500">Reference Number</span><span>{{ $walkInTicket->reference }}</span></div>
                                <div><span class="block text-xs font-semibold uppercase tracking-wide text-zinc-500">Shop Address</span><span>{{ $walkInTicket->technician?->technicianVerification?->address }}</span></div>
                                <div><span class="block text-xs font-semibold uppercase tracking-wide text-zinc-500">Issued On</span><span>{{ $walkInTicket->checked_in_at?->timezone('Asia/Manila')->format('F j, Y g:i A') }}</span></div>
                                <div><span class="block text-xs font-semibold uppercase tracking-wide text-zinc-500">Payment Method</span><span>Cash</span></div>
                            </div>
                            <div class="mt-6 rounded-xl border border-violet-200 bg-white/80 p-4 text-start dark:border-violet-400/20 dark:bg-zinc-950/40" data-app-walk-in-tracking data-walk-in-entry-id="{{ $walkInTicket->id }}" data-walk-in-active="{{ $walkInTicket->isActive() ? 'true' : 'false' }}" data-location-sharing="{{ $walkInTicket->location_sharing_enabled ? 'true' : 'false' }}">
                                <div class="flex items-start gap-3"><span class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-violet-100 text-violet-700 dark:bg-violet-500/15 dark:text-violet-300"><flux:icon name="map-pin" class="size-5" /></span><div class="min-w-0 flex-1"><div class="font-semibold text-zinc-900 dark:text-white">Share your live location?</div><p class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">Share while travelling so the shop can monitor your arrival. You can stop at any time.</p></div></div>
                                <div class="mt-4 flex flex-wrap gap-2"><flux:button type="button" size="sm" variant="primary" icon="map-pin" data-app-start-walk-in-sharing>Share Location</flux:button><flux:button type="button" size="sm" variant="subtle" data-app-decline-walk-in-sharing>Not Now</flux:button><flux:button type="button" size="sm" variant="danger" icon="x-circle" data-app-stop-walk-in-sharing>Stop Sharing</flux:button></div>
                                <p class="mt-3 text-sm text-zinc-600 dark:text-zinc-300" data-app-walk-in-sharing-status role="status" aria-live="polite">Location Sharing: {{ $walkInTicket->location_sharing_enabled ? 'Active' : 'Off' }}</p>
                            </div>
                            <div class="mt-6 flex flex-wrap justify-center gap-2">
                                <flux:button type="button" variant="primary" icon="arrow-down-tray" wire:click="downloadWalkInTicket" wire:loading.attr="disabled" wire:target="downloadWalkInTicket">
                                    <span wire:loading.remove wire:target="downloadWalkInTicket">Download</span>
                                    <span wire:loading wire:target="downloadWalkInTicket">Preparing PDF...</span>
                                </flux:button>
                                @if ($walkInTicket->isActive())
                                    <flux:button type="button" variant="danger" icon="x-circle" wire:click="prepareWalkInCancellation({{ $walkInTicket->id }})">Cancel Walk-In</flux:button>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>
            @elseif ($isBookingFinalStep)
                <div wire:key="booking-flow-contact-step" class="space-y-5">
                    <div>
                        <flux:heading size="md">{{ $isWalkInBooking ? 'Your details' : 'Set your address and number' }}</flux:heading>
                        <flux:text class="mt-1 text-zinc-500 dark:text-zinc-400">{{ $isWalkInBooking ? 'Provide your contact details and describe the repair or service you require.' : 'Confirm where and how the technician can reach you.' }}</flux:text>
                    </div>
                    @if ($bookingFlow === 'quick')
                        <flux:input wire:model="customerName" label="Your name" readonly />
                    @endif
                    @if ($isWalkInBooking)
                        <flux:input wire:model="walkInCustomerName" label="Your name" required />
                        <flux:input wire:model="walkInCustomerEmail" label="Email address" type="email" required />
                    @endif
                    <div class="grid gap-3 sm:grid-cols-[minmax(9rem,0.35fr)_minmax(0,1fr)]">
                        <flux:select wire:model="mobileCountryCode" label="Country code">
                            @foreach ($content['countryCallingCodes'] as $code => $label)<flux:select.option value="{{ $code }}">{{ $label }}</flux:select.option>@endforeach
                        </flux:select>
                        @if ($isWalkInBooking)
                            <flux:input wire:model="walkInCustomerPhone" label="Mobile number" type="tel" placeholder="917 123 4567" required />
                        @else
                            <flux:input wire:model="customerPhone" label="Mobile number" type="tel" placeholder="917 123 4567" required />
                        @endif
                    </div>
                    @unless ($isWalkInBooking)
                        <div>
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-end">
                                <div class="min-w-0 flex-1"><flux:input wire:model="address" label="Service address" placeholder="Search your service location" required /></div>
                                <flux:button type="button" variant="subtle" icon="magnifying-glass" wire:click="searchAddress" wire:loading.attr="disabled" wire:target="searchAddress">Find on map</flux:button>
                            </div>
                            @if ($addressSuggestions !== [])
                                <div class="mt-2 overflow-hidden rounded-lg border border-zinc-200 bg-white shadow-sm dark:border-white/10 dark:bg-zinc-900">
                                    @foreach ($addressSuggestions as $index => $suggestion)
                                        <button type="button" wire:key="booking-flow-address-{{ $index }}" wire:click="selectAddress({{ $index }})" class="block w-full border-b border-zinc-100 px-3 py-2.5 text-start text-sm last:border-b-0 hover:bg-violet-50 dark:border-white/10 dark:hover:bg-violet-500/10">{{ $suggestion['label'] }}</button>
                                    @endforeach
                                </div>
                            @endif
                            <div class="customer-desktop-booking-map relative mt-3 h-52 overflow-hidden rounded-xl border border-zinc-200 bg-zinc-100 dark:border-white/10 dark:bg-zinc-900" data-app-customer-booking-map data-map-center-lat="{{ $addressLatitude ?? 14.5995 }}" data-map-center-lng="{{ $addressLongitude ?? 120.9842 }}">
                                <div class="absolute inset-0" data-app-customer-map data-map-center-lat="{{ $addressLatitude ?? 14.5995 }}" data-map-center-lng="{{ $addressLongitude ?? 120.9842 }}" data-map-markers="[]" data-map-draggable="true" data-map-auto-locate="{{ $bookingFlow === 'quick' ? 'true' : 'false' }}" data-map-watch-location="false" wire:ignore><div data-app-map-status class="pointer-events-none absolute inset-0 z-[1000] flex items-center justify-center bg-zinc-100/90 text-sm font-medium text-zinc-500 dark:bg-zinc-900/90 dark:text-zinc-400" role="status" aria-live="polite">Loading live map…</div></div>
                                <div class="pointer-events-none absolute inset-x-3 bottom-3 z-[1000]"><span class="inline-flex rounded-full bg-white/95 px-3 py-1.5 text-xs font-medium text-zinc-700 shadow-md dark:bg-zinc-950/95 dark:text-zinc-200">Drag the pin to fine-tune</span></div>
                                <button type="button" data-app-map-locate class="absolute right-3 top-3 z-[1000] flex size-10 items-center justify-center rounded-full border border-zinc-200 bg-white text-zinc-800 shadow-md dark:border-white/10 dark:bg-zinc-950 dark:text-white" aria-label="Use my current location"><flux:icon name="map-pin" class="size-4" /></button>
                                <input type="hidden" wire:model.live.number="addressLatitude" data-app-map-latitude />
                                <input type="hidden" wire:model.live.number="addressLongitude" data-app-map-longitude />
                            </div>
                        </div>
                        @if ($bookingFlow === 'manual')
                            <flux:input wire:model="scheduledAt" label="Preferred date and time" type="datetime-local" min="{{ $minimumScheduledAt }}" required />
                        @endif
                    @endunless
                    @if ($isWalkInBooking)
                        <flux:textarea wire:model="description" label="Service request details" placeholder="Describe the issue, symptoms, or repair needed." rows="4" required />
                    @endif
                    <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-900 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-200">
                        <div class="flex items-center gap-2 font-semibold"><flux:icon name="banknotes" class="size-4" />Payment Method: Cash</div>
                        <p class="mt-1">Payment will be made in cash at the shop after the service.</p>
                    </div>
                </div>
            @endif

            <div class="flex flex-wrap items-center justify-between gap-3 border-t border-zinc-200 pt-3 dark:border-white/10">
                <div>
                    @if ($bookingStep > 1 && ! $isWalkInTicketStep)
                        <flux:button type="button" variant="subtle" icon="arrow-left" wire:click="previousBookingStep">Back</flux:button>
                    @endif
                </div>
                @if ($isWalkInTicketStep)
                    <flux:button type="button" variant="primary" wire:click="closeBookingFlow">Done</flux:button>
                @elseif ($isBookingFinalStep)
                    <flux:button type="submit" variant="primary" icon="arrow-right" wire:loading.attr="disabled" wire:target="submitBookingFlow">{{ $isWalkInBooking ? 'Join walk-in queue' : ($bookingFlow === 'quick' ? 'Create quick booking' : 'Book home service') }}</flux:button>
                @else
                    <flux:button type="button" variant="primary" icon="arrow-right" wire:click="nextBookingStep" wire:loading.attr="disabled" wire:target="nextBookingStep">Next</flux:button>
                @endif
            </div>
        </form>
    </flux:modal>

    <flux:modal name="customer-booking-details" class="max-w-lg" @close="closeBooking" wire:model="showBookingDetails">
        <div class="space-y-6">
            @if ($selectedBooking)
                <div class="space-y-2"><div class="flex items-center justify-between gap-3"><flux:heading size="lg">{{ $serviceLabel($selectedBooking->service_type) }}</flux:heading><x-super-admin.table-cell :value="$selectedBooking->status" type="status" /></div><flux:text class="text-zinc-500">{{ $selectedBooking->reference }}</flux:text></div>
                <div class="grid gap-4 text-sm sm:grid-cols-2"><div><flux:text class="text-zinc-500">Address</flux:text><div class="mt-1">{{ $selectedBooking->address }}</div></div><div><flux:text class="text-zinc-500">Mobile number</flux:text><div class="mt-1">{{ $selectedBooking->customer_phone ?: 'Not provided' }}</div></div><div><flux:text class="text-zinc-500">Schedule</flux:text><div class="mt-1">{{ $formatDateTime($selectedBooking->scheduled_at ?: $selectedBooking->created_at) }}</div></div><div><flux:text class="text-zinc-500">Booking type</flux:text><div class="mt-1">{{ \Illuminate\Support\Str::headline($selectedBooking->booking_type) }}</div></div><div><flux:text class="text-zinc-500">Created</flux:text><div class="mt-1">{{ $formatDate($selectedBooking->created_at) }}</div></div><div class="sm:col-span-2"><flux:text class="text-zinc-500">Description</flux:text><div class="mt-1">{{ $selectedBooking->description ?: 'No additional notes.' }}</div></div></div>
            @endif
            <div class="flex justify-end gap-2">
                @if ($selectedBooking && in_array($selectedBooking->status, \App\Models\Booking::ACTIVE_STATUSES, true))
                    <flux:button variant="danger" icon="x-circle" wire:click="prepareCancellation({{ $selectedBooking->id }})">Cancel booking</flux:button>
                @endif
                <flux:button variant="subtle" wire:click="closeBooking">Close</flux:button>
            </div>
        </div>
    </flux:modal>

    <flux:modal name="customer-payment-details" class="max-w-md" @close="closePaymentDetails" wire:model="showPaymentDetails">
        <div class="space-y-6">
            @if ($selectedPayment)
                <div class="space-y-2">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <flux:heading size="lg">Payment details</flux:heading>
                            <flux:text class="mt-1 text-zinc-500">{{ $selectedPayment->booking?->reference ?: 'Payment record' }}</flux:text>
                        </div>
                        <x-super-admin.table-cell :value="$selectedPayment->status" type="status" />
                    </div>
                </div>
                <div class="grid gap-4 text-sm sm:grid-cols-2">
                    <div><flux:text class="text-zinc-500">Service</flux:text><div class="mt-1">{{ $serviceLabel($selectedPayment->booking?->service_type) }}</div></div>
                    <div><flux:text class="text-zinc-500">Amount</flux:text><div class="mt-1 font-semibold tabular-nums">₱{{ number_format((float) $selectedPayment->amount, 2) }}</div></div>
                    <div><flux:text class="text-zinc-500">Payment method</flux:text><div class="mt-1">{{ $paymentMethodLabel($selectedPayment->method) }}</div></div>
                    <div><flux:text class="text-zinc-500">Paid at</flux:text><div class="mt-1">{{ $formatDateTime($selectedPayment->paid_at ?: $selectedPayment->created_at) }}</div></div>
                    <div class="sm:col-span-2"><flux:text class="text-zinc-500">Receipt reference</flux:text><div class="mt-1 break-all font-mono text-xs">{{ $selectedPayment->transaction_ref ?: 'Issued when the cash payment is confirmed' }}</div></div>
                </div>
            @endif
            <div class="flex justify-end">
                <flux:button variant="subtle" wire:click="closePaymentDetails">Close</flux:button>
            </div>
        </div>
    </flux:modal>

    <flux:modal name="customer-walk-in-entry-details" class="max-w-lg" @close="closeWalkInEntryDetails" wire:model="showWalkInEntryDetails">
        <div class="space-y-6">
            @if ($selectedWalkInEntry)
                <div class="flex items-start justify-between gap-3">
                    <div><flux:heading size="lg">Queue details</flux:heading><flux:text class="mt-1 text-zinc-500">{{ $selectedWalkInEntry->queue_number }}</flux:text></div>
                    <x-super-admin.table-cell :value="$selectedWalkInEntry->status" type="status" />
                </div>
                <div class="grid gap-4 text-sm sm:grid-cols-2">
                    <div><flux:text class="text-zinc-500">Reference number</flux:text><div class="mt-1 font-mono text-xs">{{ $selectedWalkInEntry->reference ?: 'Not available' }}</div></div>
                    <div><flux:text class="text-zinc-500">Service</flux:text><div class="mt-1">{{ $serviceLabel($selectedWalkInEntry->service_type) }}</div></div>
                    <div><flux:text class="text-zinc-500">Queue position</flux:text><div class="mt-1">{{ $selectedWalkInEntry->queue_position ? '#'.$selectedWalkInEntry->queue_position.' of '.$selectedWalkInEntry::MAX_ACTIVE : 'No longer active' }}</div></div>
                    <div><flux:text class="text-zinc-500">Payment method</flux:text><div class="mt-1">Cash</div></div>
                    <div><flux:text class="text-zinc-500">Checked in</flux:text><div class="mt-1">{{ $formatDateTime($selectedWalkInEntry->checked_in_at) }}</div></div>
                    <div><flux:text class="text-zinc-500">Contact number</flux:text><div class="mt-1">{{ $selectedWalkInEntry->customer_phone ?: 'Not provided' }}</div></div>
                    <div class="sm:col-span-2"><flux:text class="text-zinc-500">Shop</flux:text><div class="mt-1">{{ $selectedWalkInEntry->technician?->name ? $selectedWalkInEntry->technician->name.' Service Store' : 'Not assigned' }}</div><div class="mt-1 text-zinc-500">{{ $selectedWalkInEntry->technician?->technicianVerification?->address ?: 'Shop address not available' }}</div></div>
                    <div class="sm:col-span-2"><flux:text class="text-zinc-500">Service request details</flux:text><div class="mt-1 whitespace-pre-line">{{ $selectedWalkInEntry->notes ?: 'No additional details provided.' }}</div></div>
                </div>
                @if ($selectedWalkInEntry->isActive())
                    <div class="rounded-xl border border-zinc-200 p-4 dark:border-white/10" data-app-walk-in-tracking data-walk-in-entry-id="{{ $selectedWalkInEntry->id }}" data-walk-in-active="true" data-location-sharing="{{ $selectedWalkInEntry->location_sharing_enabled ? 'true' : 'false' }}">
                        <div class="flex items-center justify-between gap-3"><div><div class="font-semibold">Live location</div><div class="mt-1 text-sm text-zinc-500">Location Sharing: {{ $selectedWalkInEntry->location_sharing_enabled ? 'Active' : 'Off' }}</div></div><flux:icon name="map-pin" class="size-5 text-violet-500" /></div>
                        <div class="mt-4 flex flex-wrap gap-2"><flux:button type="button" size="sm" variant="primary" data-app-start-walk-in-sharing>Enable Location</flux:button><flux:button type="button" size="sm" variant="danger" data-app-stop-walk-in-sharing>Stop Sharing</flux:button></div>
                        <p class="mt-3 text-sm text-zinc-500" data-app-walk-in-sharing-status role="status" aria-live="polite">{{ $selectedWalkInEntry->location_sharing_enabled ? 'Live location is being shared.' : 'Customer location is currently unavailable.' }}</p>
                    </div>
                @endif
                @if ($selectedWalkInEntry->location_sharing_enabled && $selectedWalkInEntry->customer_latitude !== null && $selectedWalkInEntry->technician?->latitude !== null)
                    <div class="h-64 overflow-hidden rounded-xl border border-zinc-200 bg-zinc-100 dark:border-white/10 dark:bg-zinc-900" data-app-walk-in-live-map data-customer-latitude="{{ $selectedWalkInEntry->customer_latitude }}" data-customer-longitude="{{ $selectedWalkInEntry->customer_longitude }}" data-shop-latitude="{{ $selectedWalkInEntry->technician->latitude }}" data-shop-longitude="{{ $selectedWalkInEntry->technician->longitude }}" data-shop-name="{{ $selectedWalkInEntry->technician->name }} Service Store" wire:ignore></div>
                @endif
                <div>
                    <flux:text class="text-xs font-semibold uppercase tracking-wide text-zinc-500">Ticket history</flux:text>
                    <div class="mt-3 space-y-3">
                        @forelse ($selectedWalkInEntry->statusHistory as $history)
                            <div class="flex gap-3 text-sm"><span class="mt-1 size-2 shrink-0 rounded-full bg-violet-500"></span><div><div class="font-medium">{{ \Illuminate\Support\Str::headline($history->to_status) }}</div><div class="text-xs text-zinc-500">{{ $formatDateTime($history->created_at) }} · {{ $history->actor?->name ?: 'System' }}</div>@if ($history->reason)<div class="mt-1 text-zinc-600 dark:text-zinc-300">{{ $history->reason }}</div>@endif</div></div>
                        @empty
                            <div class="text-sm text-zinc-500">No status updates recorded yet.</div>
                        @endforelse
                    </div>
                </div>
            @endif
            <div class="flex flex-wrap justify-end gap-2">@if ($selectedWalkInEntry?->isActive())<flux:button variant="danger" wire:click="prepareWalkInCancellation({{ $selectedWalkInEntry->id }})">Cancel Walk-In</flux:button>@endif<flux:button variant="subtle" wire:click="closeWalkInEntryDetails">Close</flux:button></div>
        </div>
    </flux:modal>

    <flux:modal name="customer-walk-in-cancellation" class="max-w-md" @close="closeWalkInCancellation" wire:model="showWalkInCancellation">
        <form class="space-y-6" wire:submit="cancelWalkIn">
            <div class="space-y-2"><flux:heading size="lg">Cancel Walk-In ticket?</flux:heading><flux:text>Are you sure you want to cancel Walk-In ticket {{ $cancellingWalkInEntry?->queue_number }}? It will remain in your history and your queue slot will become available.</flux:text></div>
            <flux:textarea wire:model="walkInCancellationReason" label="Reason (optional)" rows="3" placeholder="Tell the shop why you are cancelling." />
            <div class="flex justify-end gap-3"><flux:button type="button" variant="outline" wire:click="closeWalkInCancellation">Keep Ticket</flux:button><flux:button type="submit" variant="danger" wire:loading.attr="disabled" wire:target="cancelWalkIn">Cancel Walk-In</flux:button></div>
        </form>
    </flux:modal>

    <flux:modal name="customer-booking-cancellation" class="max-w-md" @close="closeCancellation" wire:model="showCancellation">
        <form class="space-y-6" wire:submit="cancelBooking">
            <div class="space-y-2"><flux:heading size="lg">Cancel booking?</flux:heading><flux:text>This will release the technician assignment. Please tell us why you are cancelling.</flux:text></div>
            <flux:textarea wire:model="cancellationReason" label="Reason" rows="4" required />
            <div class="flex justify-end gap-3"><flux:button type="button" variant="outline" wire:click="closeCancellation">Keep booking</flux:button><flux:button type="submit" variant="danger" wire:loading.attr="disabled" wire:target="cancelBooking">Confirm cancellation</flux:button></div>
        </form>
    </flux:modal>
</div>
