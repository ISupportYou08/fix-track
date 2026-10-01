<x-layouts::auth :title="__('Verify technician email')">
    @php($isPendingRegistration = $pendingRegistration ?? false)
    <div class="auth-stack" data-test="technician-email-verification">
        <div class="flex flex-col gap-6">
            <header class="flex flex-col items-center gap-3 text-center">
                <div class="relative flex size-16 items-center justify-center rounded-2xl bg-blue-50 text-blue-600 ring-1 ring-blue-100">
                    <svg class="size-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <rect width="18" height="14" x="3" y="5" rx="2" />
                        <path d="m3 7 9 6 9-6" />
                    </svg>
                    <span class="absolute -end-1 -top-1 flex size-6 items-center justify-center rounded-full border-2 border-white bg-emerald-500 text-white">
                        <svg class="size-3" viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="m2.2 6.2 2.2 2.1 5.4-5" />
                        </svg>
                    </span>
                </div>

                <div class="flex flex-col gap-1.5">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-blue-600">{{ __('Email verification') }}</p>
                    <h1 class="text-2xl font-semibold tracking-[-0.025em] text-[#16233b]">{{ __('Confirm your email') }}</h1>
                    <p class="text-sm leading-6 text-zinc-500">{{ __('Enter the 6-digit code we sent to your email address.') }}</p>
                </div>

                <div class="inline-flex max-w-full items-center gap-2 rounded-full border border-zinc-200 bg-white px-3.5 py-2 text-sm font-medium text-zinc-700 shadow-sm" title="{{ $technician->email }}">
                    <svg class="size-4 shrink-0 text-blue-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M4 6.5h16v11H4z" />
                        <path d="m4 7 8 6 8-6" />
                    </svg>
                    <span class="truncate">{{ $technician->email }}</span>
                </div>
            </header>

            @if (session('status'))
                <div class="flex items-start gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm leading-5 text-emerald-800" role="status">
                    <svg class="mt-0.5 size-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M20 6 9 17l-5-5" />
                    </svg>
                    <span>{{ session('status') }}</span>
                </div>
            @endif

            @if (session('email_delivery_error'))
                <div class="flex items-start gap-3 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm leading-5 text-red-700" role="alert">
                    <svg class="mt-0.5 size-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <circle cx="12" cy="12" r="9" />
                        <path d="M12 8v4M12 16h.01" />
                    </svg>
                    <span>{{ session('email_delivery_error') }}</span>
                </div>
            @endif

            @error('email')
                <div class="flex items-start gap-3 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm leading-5 text-red-700" role="alert">
                    <svg class="mt-0.5 size-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <circle cx="12" cy="12" r="9" />
                        <path d="M12 8v4M12 16h.01" />
                    </svg>
                    <span>{{ $message }}</span>
                </div>
            @enderror

            @if ($isPendingRegistration)
                <form method="POST" action="{{ route('technician.registration.email.update') }}" class="flex flex-col gap-2 rounded-xl border border-zinc-200 bg-white p-4">
                    @csrf
                    <label for="technician-new-email" class="text-sm font-semibold text-zinc-800">{{ __('Change email address') }}</label>
                    <div class="flex flex-col gap-2 sm:flex-row">
                        <input id="technician-new-email" name="email" type="email" value="{{ old('email', $technician->email) }}" required autocomplete="email" class="min-h-11 min-w-0 flex-1 rounded-lg border border-zinc-300 px-3 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                        <button type="submit" class="min-h-11 rounded-lg bg-zinc-800 px-4 text-sm font-semibold text-white hover:bg-zinc-900">{{ __('Send code to new email') }}</button>
                    </div>
                    <p class="text-xs text-zinc-500">{{ __('Changing your email sends a new code and invalidates the previous one.') }}</p>
                </form>
            @endif

            <form method="POST" action="{{ $isPendingRegistration ? route('technician.registration.verify') : route('technician.email-otp.verify') }}" class="flex flex-col gap-4">
                @csrf

                <div class="flex flex-col gap-2">
                    <div class="flex items-center justify-between gap-3">
                        <label for="verification-code" class="text-sm font-semibold text-zinc-800">{{ __('Verification code') }}</label>
                        <span class="text-xs text-zinc-400">{{ __('Expires in 10 minutes') }}</span>
                    </div>
                    <input
                        id="verification-code"
                        name="code"
                        type="text"
                        inputmode="numeric"
                        pattern="[0-9]{6}"
                        maxlength="6"
                        class="w-full rounded-xl border bg-white px-4 py-3.5 text-center text-2xl font-semibold tracking-[0.45em] text-[#16233b] shadow-sm outline-none transition placeholder:text-zinc-300 focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 @error('code') border-red-400 focus:border-red-500 focus:ring-red-500/10 @else border-zinc-200 @enderror"
                        placeholder="000000"
                        aria-describedby="verification-code-help @error('code') verification-code-error @enderror"
                        required
                        autofocus
                        autocomplete="one-time-code"
                    >
                    <p id="verification-code-help" class="text-xs leading-5 text-zinc-500">{{ __('For your security, never share this code with anyone.') }}</p>
                    @error('code')
                        <p id="verification-code-error" class="flex items-center gap-1.5 text-sm text-red-600" role="alert">
                            <svg class="size-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <circle cx="12" cy="12" r="9" />
                                <path d="M12 8v4M12 16h.01" />
                            </svg>
                            <span>{{ $message }}</span>
                        </p>
                    @enderror
                </div>

                <button type="submit" class="flex min-h-12 w-full items-center justify-center gap-2 rounded-xl bg-blue-600 px-4 py-3 text-sm font-semibold text-white shadow-sm shadow-blue-600/20 transition hover:-translate-y-0.5 hover:bg-blue-700 hover:shadow-md focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-blue-500/25 active:translate-y-0">
                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M20 6 9 17l-5-5" />
                    </svg>
                    {{ __('Verify email') }}
                </button>
            </form>

            <div class="flex flex-col gap-3 rounded-xl border border-zinc-200 bg-zinc-50/80 p-4 text-center">
                <div class="flex flex-col gap-1">
                    <p class="text-sm font-semibold text-zinc-800">{{ __('Didn’t receive the email?') }}</p>
                    <p class="text-xs leading-5 text-zinc-500">{{ __('Check your spam folder or request a fresh code.') }}</p>
                </div>
                <form method="POST" action="{{ $isPendingRegistration ? route('technician.registration.resend') : route('technician.email-otp.resend') }}">
                    @csrf
                    <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-lg px-3 py-2 text-sm font-semibold text-blue-600 transition hover:bg-blue-50 hover:text-blue-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500/30">
                        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M20 11a8.1 8.1 0 0 0-15.5-2M4 4v5h5" />
                            <path d="M4 13a8.1 8.1 0 0 0 15.5 2M20 20v-5h-5" />
                        </svg>
                        {{ __('Send a new code') }}
                    </button>
                </form>
            </div>

            <div class="flex items-start gap-3 rounded-xl border border-blue-100 bg-blue-50/60 p-4 text-sm leading-5 text-blue-950">
                <span class="flex size-7 shrink-0 items-center justify-center rounded-full bg-blue-100 text-xs font-bold text-blue-700" aria-hidden="true">1</span>
                <div class="flex flex-col gap-1">
                    <strong>{{ __('What happens next?') }}</strong>
                    <span class="text-blue-900/75">{{ __('After verification, FixTrack staff will review your application. Your technician account stays limited until approval.') }}</span>
                </div>
            </div>

            @if ($isPendingRegistration)
                <a href="{{ route('register') }}" class="text-center text-sm font-medium text-zinc-500 transition hover:text-blue-600">{{ __('Start a new registration') }}</a>
            @else
                <form method="POST" action="{{ route('logout') }}" class="text-center">
                    @csrf
                    <button type="submit" class="text-sm font-medium text-zinc-500 transition hover:text-blue-600 focus-visible:rounded focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500/30">
                        {{ __('Sign out and use another account') }}
                    </button>
                </form>
            @endif
        </div>
    </div>
</x-layouts::auth>
