@php
    $isStaffPortal = ($portal ?? 'admin') === 'staff';
    $portalTitle = $isStaffPortal ? __('Staff sign in') : __('Administrator sign in');
    $portalLabel = $isStaffPortal ? __('Staff') : __('Administrator');
    $portalRoute = $isStaffPortal ? 'staff.login.store' : 'admin.login.store';
@endphp

<x-layouts::auth.admin :title="$portalTitle">
    <main class="grid min-h-screen place-items-center p-4 sm:p-6 lg:p-8" data-test="{{ $isStaffPortal ? 'staff-login-page' : 'admin-login-page' }}">
        <div class="grid w-full max-w-6xl overflow-hidden rounded-[2.5rem] border border-white/50 bg-white/10 shadow-[0_30px_60px_-20px_rgba(0,0,0,0.4)] backdrop-blur-sm lg:grid-cols-[5fr_7fr]">
            <section class="border-b border-white/15 bg-black/40 px-8 py-12 text-white backdrop-blur-xl lg:border-b-0 lg:border-r lg:px-12 lg:py-16" aria-labelledby="admin-portal-heading">
                <div class="mx-auto w-full max-w-md lg:mx-0">
                    <a href="{{ route('home') }}" class="inline-flex items-center gap-3">
                        <span class="grid size-10 place-items-center rounded-xl border border-white/20 bg-white/10 text-xl font-bold tracking-tighter shadow-lg backdrop-blur-md" aria-hidden="true">F</span>
                        <span class="text-2xl font-bold tracking-tight text-white drop-shadow-sm">FixTrack</span>
                    </a>

                    <div class="mt-8">
                        <span class="inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/10 px-3 py-1.5 text-xs font-bold uppercase tracking-wider text-white shadow-sm backdrop-blur-sm">
                            <span class="size-1.5 rounded-full bg-emerald-400 shadow-[0_0_8px_rgba(52,211,153,0.8)] motion-safe:animate-pulse" aria-hidden="true"></span>
                            Protected Workspace
                        </span>
                    </div>

                    <h1 id="admin-portal-heading" class="mt-6 text-4xl font-extrabold leading-[1.1] tracking-tight text-white drop-shadow-md lg:text-5xl">
                        Welcome,<br>{{ $portalLabel }}
                    </h1>
                    <p class="mt-5 max-w-md text-base font-light leading-relaxed text-slate-200 drop-shadow-sm">
                        {{ $isStaffPortal
                            ? __('Manage service operations, assist users, and monitor platform activity from one secure workspace.')
                            : __('Securely manage users, configure system settings, and monitor platform performance from one centralized, encrypted hub.') }}
                    </p>

                    <div class="mt-10 grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <div class="rounded-xl border border-white/10 bg-black/30 p-4 shadow-lg backdrop-blur-lg transition duration-200 hover:-translate-y-0.5 hover:border-white/20 hover:bg-black/40">
                            <div class="mb-3 grid size-8 place-items-center rounded-lg bg-white/10 text-white">
                                <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                </svg>
                            </div>
                            <p class="text-xs font-semibold leading-tight text-white/90">User Management</p>
                        </div>
                        <div class="rounded-xl border border-white/10 bg-black/30 p-4 shadow-lg backdrop-blur-lg transition duration-200 hover:-translate-y-0.5 hover:border-white/20 hover:bg-black/40">
                            <div class="mb-3 grid size-8 place-items-center rounded-lg bg-white/10 text-white">
                                <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z" />
                                </svg>
                            </div>
                            <p class="text-xs font-semibold leading-tight text-white/90">System Analytics</p>
                        </div>
                        <div class="rounded-xl border border-white/10 bg-black/30 p-4 shadow-lg backdrop-blur-lg transition duration-200 hover:-translate-y-0.5 hover:border-white/20 hover:bg-black/40">
                            <div class="mb-3 grid size-8 place-items-center rounded-lg bg-white/10 text-white">
                                <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                </svg>
                            </div>
                            <p class="text-xs font-semibold leading-tight text-white/90">Security Audits</p>
                        </div>
                    </div>
                </div>
            </section>

            <section class="grid place-items-center bg-white/40 px-6 py-12 backdrop-blur-xl sm:px-8 lg:px-12" aria-labelledby="admin-sign-in-heading">
                <div class="w-full max-w-[460px]">
                    <div class="rounded-[2rem] border border-white/90 bg-white/80 p-8 shadow-[0_20px_50px_-15px_rgba(0,0,0,0.2)] backdrop-blur-2xl sm:p-10">
                        <div class="flex items-center justify-between gap-4">
                            <h2 id="admin-sign-in-heading" class="text-3xl font-extrabold tracking-tight text-slate-900">{{ $isStaffPortal ? __('Staff Sign In') : __('Admin Sign In') }}</h2>
                            <div class="grid size-12 shrink-0 place-items-center rounded-xl border border-white/90 bg-white/90 text-slate-800 shadow-sm" aria-hidden="true">
                                <svg class="size-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                                </svg>
                            </div>
                        </div>
                        <p class="mt-2 text-sm font-medium text-slate-600">{{ $isStaffPortal ? __('Enter your staff credentials to continue.') : __('Enter your administrative credentials to continue.') }}</p>

                        @if ($errors->any())
                            <div id="admin-login-error" class="mt-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700" role="alert">
                                {{ $errors->first() }}
                            </div>
                        @endif

                        <form method="POST" action="{{ route($portalRoute) }}" class="mt-8 grid gap-5" x-data="{ showPassword: false }">
                            @csrf

                            <div class="grid gap-2">
                                <label for="admin-email" class="text-xs font-bold uppercase tracking-wide text-slate-700">{{ $isStaffPortal ? __('Staff Email') : __('Admin Email') }}</label>
                                <div class="group flex items-center overflow-hidden rounded-xl border border-slate-200 bg-white transition-colors focus-within:border-slate-400">
                                    <span class="pl-4 pr-3 text-slate-400 transition-colors group-focus-within:text-slate-800" aria-hidden="true">
                                        <svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207" />
                                        </svg>
                                    </span>
                                    <input id="admin-email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="username" placeholder="Enter your email" class="admin-login-input w-full min-w-0 bg-transparent py-4 pr-4 text-base font-medium text-slate-900 outline-none placeholder:text-slate-500" aria-invalid="{{ $errors->has('email') ? 'true' : 'false' }}">
                                </div>
                            </div>

                            <div class="grid gap-2">
                                <label for="admin-password" class="text-xs font-bold uppercase tracking-wide text-slate-700">Password</label>
                                <div class="group flex items-center overflow-hidden rounded-xl border border-slate-200 bg-white transition-colors focus-within:border-slate-400">
                                    <span class="pl-4 pr-3 text-slate-400 transition-colors group-focus-within:text-slate-800" aria-hidden="true">
                                        <svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                        </svg>
                                    </span>
                                    <input id="admin-password" name="password" type="password" x-bind:type="showPassword ? 'text' : 'password'" required autocomplete="current-password" placeholder="Enter your password" class="admin-login-input w-full min-w-0 bg-transparent py-4 pr-2 text-base font-medium text-slate-900 outline-none placeholder:text-slate-500">
                                    <button type="button" x-on:click="showPassword = ! showPassword" x-bind:aria-label="showPassword ? 'Hide password' : 'Show password'" x-bind:aria-pressed="showPassword" class="grid size-12 shrink-0 place-items-center text-slate-400 transition-colors hover:text-slate-700 focus-visible:outline-2 focus-visible:outline-offset-[-4px] focus-visible:outline-slate-700" aria-label="Show password">
                                        <svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            <label class="flex w-fit cursor-pointer items-center gap-3 py-2 text-sm font-semibold text-slate-700">
                                <input name="remember" type="checkbox" value="1" @checked(old('remember')) class="size-5 cursor-pointer rounded-md border-2 border-slate-400 bg-white/80 accent-slate-900">
                                <span>Stay logged in</span>
                            </label>

                            <button type="submit" class="group flex w-full items-center justify-center gap-2 rounded-xl bg-slate-900 px-6 py-4 text-base font-bold text-white shadow-lg transition-all duration-300 hover:-translate-y-px hover:bg-slate-800 hover:shadow-xl focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-slate-900" data-test="{{ $isStaffPortal ? 'staff-login-button' : 'admin-login-button' }}">
                                <span>Log in</span>
                                <svg class="size-5 transition-transform group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                </svg>
                            </button>
                        </form>
                    </div>
                    <p class="mt-6 text-center text-sm font-medium text-slate-800">
                        Customer or technician?
                        <a href="{{ route('login') }}" class="underline decoration-slate-500 underline-offset-4 transition hover:text-slate-600">Use the standard sign in</a>
                    </p>
                </div>
            </section>
        </div>
    </main>
</x-layouts::auth.admin>
