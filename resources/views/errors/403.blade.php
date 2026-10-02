@php
    $user = auth()->user();
    $requestedWorkspace = match (true) {
        request()->is('admin', 'admin/*') => __('Administrator workspace'),
        request()->is('staff', 'staff/*') => __('Staff workspace'),
        request()->is('technician', 'technician/*') => __('Technician workspace'),
        request()->is('customer', 'customer/*') => __('Customer workspace'),
        default => __('this page'),
    };
    $accountType = match (true) {
        $user?->isSuperAdmin() => __('Administrator'),
        $user?->isStaff() => __('Staff'),
        $user?->isTechnician() => __('Technician'),
        $user?->isCustomer() => __('Customer'),
        default => __('your current'),
    };
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('partials.head', ['title' => __('Access restricted')])
</head>
<body class="min-h-screen bg-slate-950 text-slate-900 antialiased">
    <main class="relative grid min-h-dvh place-items-center overflow-hidden px-4 py-8 sm:px-6">
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_left,rgba(14,165,233,0.3),transparent_35%),radial-gradient(circle_at_bottom_right,rgba(139,92,246,0.3),transparent_40%)]" aria-hidden="true"></div>

        <section class="relative w-full max-w-xl overflow-hidden rounded-3xl border border-white/20 bg-white/95 p-6 shadow-2xl backdrop-blur sm:p-10" aria-labelledby="access-restricted-heading">
            <div class="grid size-14 place-items-center rounded-2xl bg-amber-100 text-amber-700" aria-hidden="true">
                <svg class="size-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 9v4" />
                    <path d="M12 17h.01" />
                    <path d="M10.3 2.9 1.8 17a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 2.9a2 2 0 0 0-3.4 0Z" />
                </svg>
            </div>

            <p class="mt-6 text-sm font-bold uppercase tracking-[0.18em] text-sky-700">{{ __('Error 403') }}</p>
            <h1 id="access-restricted-heading" class="mt-2 text-3xl font-extrabold tracking-tight text-slate-950 sm:text-4xl">{{ __('Access restricted') }}</h1>
            <p class="mt-4 text-base leading-7 text-slate-600">
                {{ __('You are signed in with a :account account. That account cannot open the :workspace.', ['account' => $accountType, 'workspace' => $requestedWorkspace]) }}
            </p>

            <div class="mt-8 grid gap-3 sm:grid-cols-2">
                @auth
                    <a href="{{ route('dashboard') }}" class="inline-flex min-h-12 items-center justify-center rounded-xl bg-slate-950 px-5 py-3 text-center text-sm font-bold text-white shadow-lg transition hover:bg-slate-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sky-600">
                        {{ __('Go to my dashboard') }}
                    </a>

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="inline-flex min-h-12 w-full items-center justify-center rounded-xl border border-slate-300 bg-white px-5 py-3 text-sm font-bold text-slate-800 transition hover:bg-slate-50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sky-600">
                            {{ __('Sign in with another account') }}
                        </button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="inline-flex min-h-12 items-center justify-center rounded-xl bg-slate-950 px-5 py-3 text-center text-sm font-bold text-white shadow-lg transition hover:bg-slate-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sky-600 sm:col-span-2">
                        {{ __('Return to sign in') }}
                    </a>
                @endauth
            </div>
        </section>
    </main>
</body>
</html>
