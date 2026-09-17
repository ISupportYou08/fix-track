<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        @include('partials.head', ['title' => 'Fast, reliable repair services'])
        <meta name="description" content="Book a trusted technician or join the live Walk-In queue with FixTrack.">
    </head>
    <body class="min-h-screen bg-zinc-50 font-sans text-zinc-950 antialiased dark:bg-zinc-950 dark:text-white">
        @php
            $bookUrl = auth()->check() ? route('dashboard') : route('register');
            $walkInUrl = auth()->check() && auth()->user()->isCustomer()
                ? route('customer.module', ['module' => 'walk-in-queue'])
                : (auth()->check() ? route('dashboard') : route('register'));
        @endphp

        <header class="sticky top-0 z-50 border-b border-zinc-200/80 bg-white/90 backdrop-blur-xl dark:border-white/10 dark:bg-zinc-950/90">
            <div class="mx-auto flex h-18 max-w-7xl items-center justify-between gap-5 px-5 sm:px-8 lg:px-10">
                <a href="{{ route('home') }}" class="flex items-center gap-3" aria-label="FixTrack home">
                    <span class="grid size-10 place-items-center rounded-xl bg-white shadow-sm ring-1 ring-zinc-200 dark:bg-zinc-900 dark:ring-white/10"><img src="{{ asset('fixtrack-logo.png') }}" alt="" class="size-8 object-contain"></span>
                    <span class="text-lg font-bold tracking-tight">FixTrack</span>
                </a>
                <nav class="hidden items-center gap-7 text-sm font-medium text-zinc-600 dark:text-zinc-300 md:flex" aria-label="Main navigation">
                    <a href="#services" class="hover:text-violet-600 dark:hover:text-violet-300">Services</a>
                    <a href="#how-it-works" class="hover:text-violet-600 dark:hover:text-violet-300">How it works</a>
                    <a href="#walk-in" class="hover:text-violet-600 dark:hover:text-violet-300">Walk-In</a>
                </nav>
                <div class="flex items-center gap-2">
                    <button type="button" x-data @click="$flux.appearance = $flux.dark ? 'light' : 'dark'" class="grid size-10 place-items-center rounded-xl border border-zinc-200 bg-white text-zinc-600 transition hover:bg-zinc-100 dark:border-white/10 dark:bg-zinc-900 dark:text-zinc-300 dark:hover:bg-zinc-800" aria-label="Toggle light and dark theme">
                        <svg class="size-4 dark:hidden" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M10 2v2m0 12v2M2 10h2m12 0h2M4.3 4.3l1.4 1.4m8.6 8.6 1.4 1.4m0-11.4-1.4 1.4m-8.6 8.6-1.4 1.4" stroke-linecap="round"/><circle cx="10" cy="10" r="3.5"/></svg>
                        <svg class="hidden size-4 dark:block" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M16.5 12.5A7 7 0 0 1 7.5 3.5a7 7 0 1 0 9 9Z" stroke-linejoin="round"/></svg>
                    </button>
                    @auth
                        <a href="{{ route('dashboard') }}" class="inline-flex min-h-10 items-center rounded-xl bg-zinc-950 px-4 text-sm font-semibold text-white hover:bg-violet-700 dark:bg-white dark:text-zinc-950 dark:hover:bg-violet-200">Dashboard</a>
                    @else
                        <a data-test="home-login" href="{{ route('login') }}" class="hidden min-h-10 items-center px-3 text-sm font-semibold text-zinc-700 hover:text-violet-700 dark:text-zinc-200 dark:hover:text-violet-300 sm:inline-flex">Log in</a>
                        <a data-test="home-register" href="{{ route('register') }}" class="inline-flex min-h-10 items-center rounded-xl bg-violet-600 px-4 text-sm font-semibold text-white shadow-sm hover:bg-violet-700">Register</a>
                    @endauth
                </div>
            </div>
        </header>

        <main>
            <section class="relative isolate overflow-hidden">
                <div class="pointer-events-none absolute inset-0 -z-10 bg-[radial-gradient(circle_at_78%_20%,rgba(124,58,237,0.16),transparent_28%),radial-gradient(circle_at_10%_5%,rgba(14,165,233,0.10),transparent_25%)] dark:bg-[radial-gradient(circle_at_78%_20%,rgba(139,92,246,0.22),transparent_30%),radial-gradient(circle_at_10%_5%,rgba(14,165,233,0.12),transparent_25%)]"></div>
                <div class="mx-auto grid max-w-7xl items-center gap-12 px-5 py-20 sm:px-8 lg:grid-cols-[1fr_0.9fr] lg:px-10 lg:py-28">
                    <div class="max-w-2xl">
                        <span class="inline-flex items-center gap-2 rounded-full border border-violet-200 bg-white/80 px-3 py-1.5 text-xs font-semibold text-violet-700 shadow-sm dark:border-violet-400/30 dark:bg-zinc-900/80 dark:text-violet-300"><span class="size-2 rounded-full bg-emerald-500"></span>Professional service from booking to completion</span>
                        <h1 class="mt-7 text-4xl font-bold leading-tight tracking-[-0.04em] sm:text-6xl">Fast, reliable device repair <span class="text-violet-600 dark:text-violet-400">you can trust.</span></h1>
                        <p class="mt-6 max-w-xl text-lg leading-8 text-zinc-600 dark:text-zinc-300">Book an appointment or join our Walk-In Queue and track your service from start to finish.</p>
                        <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                            <a href="{{ $bookUrl }}" class="inline-flex min-h-12 items-center justify-center rounded-xl bg-violet-600 px-6 text-sm font-semibold text-white shadow-lg shadow-violet-600/20 transition hover:-translate-y-0.5 hover:bg-violet-700">Book a Service</a>
                            <a href="{{ $walkInUrl }}" class="inline-flex min-h-12 items-center justify-center rounded-xl border border-zinc-200 bg-white px-6 text-sm font-semibold text-zinc-800 shadow-sm transition hover:-translate-y-0.5 hover:border-violet-300 dark:border-white/10 dark:bg-zinc-900 dark:text-white dark:hover:border-violet-400">Join Walk-In Queue</a>
                        </div>
                        <div class="mt-8 flex flex-wrap gap-x-6 gap-y-3 text-sm text-zinc-500 dark:text-zinc-400">
                            @foreach (['Verified technicians', 'Live service updates', 'Cash payment'] as $benefit)
                                <span class="flex items-center gap-2"><span class="grid size-5 place-items-center rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300">✓</span>{{ $benefit }}</span>
                            @endforeach
                        </div>
                    </div>

                    <div class="rounded-[2rem] border border-zinc-200 bg-white p-5 shadow-2xl shadow-zinc-950/10 dark:border-white/10 dark:bg-zinc-900 dark:shadow-black/30 sm:p-7">
                        <div class="flex items-center justify-between gap-4"><div><p class="text-xs font-semibold uppercase tracking-wider text-violet-600 dark:text-violet-300">Service made simple</p><h2 class="mt-2 text-2xl font-bold">Choose how you want help</h2></div><span class="grid size-12 place-items-center rounded-2xl bg-violet-100 text-2xl dark:bg-violet-500/15">🛠️</span></div>
                        <div class="mt-6 space-y-3">
                            <a href="{{ $bookUrl }}" class="flex items-center gap-4 rounded-2xl border border-zinc-200 p-4 transition hover:border-violet-300 hover:bg-violet-50 dark:border-white/10 dark:hover:border-violet-400/40 dark:hover:bg-violet-500/10"><span class="grid size-11 place-items-center rounded-xl bg-zinc-950 text-white dark:bg-white dark:text-zinc-950">01</span><span class="min-w-0 flex-1"><span class="block font-semibold">Schedule a service</span><span class="mt-1 block text-sm text-zinc-500">Choose a preferred date and location.</span></span><span aria-hidden="true">→</span></a>
                            <a href="{{ $walkInUrl }}" class="flex items-center gap-4 rounded-2xl border border-violet-200 bg-violet-50/70 p-4 transition hover:border-violet-400 dark:border-violet-400/30 dark:bg-violet-500/10"><span class="grid size-11 place-items-center rounded-xl bg-violet-600 text-white">02</span><span class="min-w-0 flex-1"><span class="block font-semibold">Join the Walk-In queue</span><span class="mt-1 block text-sm text-zinc-500 dark:text-zinc-400">Reserve one of {{ $walkInCapacity }} live queue slots.</span></span><span aria-hidden="true">→</span></a>
                        </div>
                        <div class="mt-5 rounded-2xl bg-zinc-950 p-4 text-white dark:bg-white dark:text-zinc-950"><div class="flex items-center justify-between gap-4"><div><p class="text-xs text-zinc-400 dark:text-zinc-600">Walk-In availability</p><p class="mt-1 font-semibold">{{ $walkInAvailableSlots }} of {{ $walkInCapacity }} slots available</p></div><span class="size-3 rounded-full {{ $walkInAvailableSlots > 0 ? 'bg-emerald-400' : 'bg-red-400' }}"></span></div></div>
                    </div>
                </div>
            </section>

            <section class="border-y border-zinc-200 bg-white dark:border-white/10 dark:bg-zinc-900/50" aria-label="FixTrack statistics">
                <div class="mx-auto grid max-w-7xl grid-cols-2 gap-px px-5 py-10 sm:px-8 lg:grid-cols-4 lg:px-10">
                    @foreach ($statistics as $stat)
                        <div class="px-4 py-3 text-center"><div class="text-3xl font-bold tracking-tight">{{ number_format($stat['value']) }}</div><div class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ $stat['label'] }}</div></div>
                    @endforeach
                </div>
            </section>

            <section id="services" class="mx-auto max-w-7xl px-5 py-20 sm:px-8 lg:px-10">
                <div class="max-w-2xl"><p class="text-sm font-semibold text-violet-600 dark:text-violet-300">Popular services</p><h2 class="mt-2 text-3xl font-bold tracking-tight sm:text-4xl">Repairs for the things you use every day</h2><p class="mt-4 text-zinc-600 dark:text-zinc-400">Choose a service and share the issue. A qualified technician will handle the next step.</p></div>
                <div class="mt-10 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($services as $service)
                        <article class="group rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm transition hover:-translate-y-1 hover:border-violet-300 hover:shadow-lg dark:border-white/10 dark:bg-zinc-900 dark:hover:border-violet-400/40">
                            <div class="flex items-start justify-between gap-3"><span class="grid size-11 place-items-center rounded-xl bg-violet-100 font-bold text-violet-700 dark:bg-violet-500/15 dark:text-violet-300">{{ strtoupper(substr($service->code, 0, 2)) }}</span><span class="rounded-full bg-zinc-100 px-2.5 py-1 text-xs text-zinc-600 dark:bg-white/10 dark:text-zinc-300">{{ $service->category }}</span></div>
                            <h3 class="mt-5 text-lg font-semibold">{{ $service->name }}</h3><p class="mt-2 min-h-12 text-sm leading-6 text-zinc-500 dark:text-zinc-400">{{ $service->description }}</p>
                            <a href="{{ $bookUrl }}" class="mt-5 inline-flex items-center gap-2 text-sm font-semibold text-violet-700 dark:text-violet-300">Book Service <span aria-hidden="true">→</span></a>
                        </article>
                    @endforeach
                </div>
            </section>

            <section id="how-it-works" class="bg-zinc-100 dark:bg-zinc-900/60">
                <div class="mx-auto max-w-7xl px-5 py-20 sm:px-8 lg:px-10"><div class="text-center"><p class="text-sm font-semibold text-violet-600 dark:text-violet-300">How it works</p><h2 class="mt-2 text-3xl font-bold tracking-tight">Five clear steps</h2></div><div class="mt-10 grid gap-4 sm:grid-cols-2 lg:grid-cols-5">@foreach (['Choose a Service', 'Book or Join Walk-In', 'Visit the Shop', 'Technician Repairs', 'Pay Cash After Service'] as $step)<div class="rounded-2xl border border-zinc-200 bg-white p-5 dark:border-white/10 dark:bg-zinc-950"><span class="text-sm font-bold text-violet-600 dark:text-violet-300">0{{ $loop->iteration }}</span><p class="mt-4 font-semibold">{{ $step }}</p></div>@endforeach</div></div>
            </section>

            <section id="walk-in" class="mx-auto max-w-7xl px-5 py-20 sm:px-8 lg:px-10">
                <div class="overflow-hidden rounded-[2rem] bg-violet-600 px-6 py-10 text-white shadow-xl shadow-violet-600/20 sm:px-10 lg:flex lg:items-center lg:justify-between lg:gap-12 lg:px-14 lg:py-14"><div class="max-w-2xl"><p class="text-sm font-semibold text-violet-100">Need service today?</p><h2 class="mt-3 text-3xl font-bold tracking-tight sm:text-4xl">Join our Walk-In Queue before arriving.</h2><p class="mt-4 text-violet-100">Reserve your place, see your position, and optionally share your live location with the assigned shop.</p></div><div class="mt-8 rounded-2xl bg-white/10 p-5 lg:mt-0 lg:min-w-72"><div class="text-sm text-violet-100">Current availability</div><div class="mt-2 text-3xl font-bold">{{ $walkInAvailableSlots }} / {{ $walkInCapacity }} slots</div><a href="{{ $walkInUrl }}" class="mt-5 inline-flex min-h-11 w-full items-center justify-center rounded-xl bg-white px-5 text-sm font-semibold text-violet-700 hover:bg-violet-50">Join Walk-In Queue</a></div></div>
            </section>

            <section class="mx-auto max-w-7xl px-5 pb-20 sm:px-8 lg:px-10"><div class="text-center"><p class="text-sm font-semibold text-violet-600 dark:text-violet-300">Why FixTrack</p><h2 class="mt-2 text-3xl font-bold tracking-tight">A clearer repair experience</h2></div><div class="mt-10 grid gap-4 sm:grid-cols-2 lg:grid-cols-5">@foreach ([['Easy Online Booking','Book from any device.'],['Live Walk-In Tracking','Share progress when you choose.'],['Professional Technicians','Work matched by verified skills.'],['Transparent Status','Follow each service stage.'],['Cash Payment','Pay in cash after service.']] as [$title,$copy])<div class="rounded-2xl border border-zinc-200 bg-white p-5 text-center dark:border-white/10 dark:bg-zinc-900"><span class="mx-auto grid size-9 place-items-center rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300">✓</span><h3 class="mt-4 font-semibold">{{ $title }}</h3><p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">{{ $copy }}</p></div>@endforeach</div></section>

            <section class="border-t border-zinc-200 bg-white dark:border-white/10 dark:bg-zinc-900"><div class="mx-auto flex max-w-7xl flex-col items-start justify-between gap-6 px-5 py-14 sm:px-8 lg:flex-row lg:items-center lg:px-10"><div><h2 class="text-3xl font-bold tracking-tight">Ready to get your device repaired?</h2><p class="mt-2 text-zinc-500 dark:text-zinc-400">Choose an appointment or reserve a Walk-In slot.</p></div><div class="flex flex-wrap gap-3"><a href="{{ $bookUrl }}" class="inline-flex min-h-12 items-center rounded-xl bg-zinc-950 px-6 text-sm font-semibold text-white dark:bg-white dark:text-zinc-950">Book Now</a><a href="{{ $walkInUrl }}" class="inline-flex min-h-12 items-center rounded-xl border border-zinc-200 px-6 text-sm font-semibold dark:border-white/10">Join Walk-In</a></div></div></section>
        </main>

        <footer class="border-t border-zinc-200 bg-white dark:border-white/10 dark:bg-zinc-950"><div class="mx-auto flex max-w-7xl flex-col gap-3 px-5 py-8 text-sm text-zinc-500 sm:flex-row sm:items-center sm:justify-between sm:px-8 lg:px-10"><p>© {{ now()->year }} FixTrack. Repair service made visible.</p><p>Appointment and Walk-In service · Cash payment</p></div></footer>
        @fluxScripts
    </body>
</html>
