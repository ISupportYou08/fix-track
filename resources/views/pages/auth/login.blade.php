<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('partials.head', ['title' => __('Sign In')])
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Pacifico&display=swap" rel="stylesheet">
    <style>
        .sign-in-page { font-family: Inter, sans-serif; }
        .sign-in-brand { font-family: Pacifico, cursive; }
        .sign-in-input { width: 100%; padding: .875rem 3rem .875rem 2.75rem; border: 2px solid #cbd5e1; border-radius: .75rem; outline: none; color: #1f2937; background: #fff; box-shadow: 0 4px 6px -1px rgb(0 0 0 / .1), 0 2px 4px -1px rgb(0 0 0 / .06); transition: border-color .3s ease, box-shadow .3s ease; }
        .sign-in-input:focus { border-color: #0091d5; box-shadow: 0 4px 12px rgb(0 145 213 / .2); }
        .sign-in-input[aria-invalid="true"] { border-color: #ef4444; }
        .sign-in-input:-webkit-autofill, .sign-in-input:autofill { -webkit-box-shadow: 0 0 0 1000px #fff inset; box-shadow: 0 0 0 1000px #fff inset; -webkit-text-fill-color: #1f2937; }
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="sign-in-page min-h-screen overflow-x-hidden bg-gray-50 antialiased">
    <main class="flex min-h-screen flex-col lg:flex-row" data-test="customer-technician-sign-in"
        x-data='{
            submitting: false, showPassword: false, success: false, clientError: "",
            successTitle: "Welcome back",
            showLoginSuccess(redirectUrl) {
                this.success = true;
                window.setTimeout(() => window.location.assign(redirectUrl), 1100);
            },
            async submitLogin(event) {
                event.preventDefault();
                if (this.submitting) return;
                this.submitting = true;
                this.clientError = "";
                const form = event.currentTarget;
                try {
                    const response = await fetch(form.action, {
                        method: "POST", body: new FormData(form), credentials: "same-origin",
                        headers: { Accept: "application/json", "X-Requested-With": "XMLHttpRequest" }
                    });
                    const payload = await response.clone().json().catch(() => ({}));
                    if (payload.two_factor) {
                        window.location.assign("{{ route('two-factor.login') }}");
                        return;
                    }
                    if (payload.two_factor === false) {
                        this.showLoginSuccess("{{ route('dashboard') }}");
                        return;
                    }
                    if (response.redirected && response.url.includes("two-factor-challenge")) {
                        window.location.assign(response.url);
                        return;
                    }
                    if (response.ok && response.url && !response.url.endsWith("/login")) {
                        this.showLoginSuccess(response.url);
                        return;
                    }
                    this.clientError = payload.errors?.email?.[0] || payload.message || "These credentials do not match our records.";
                } catch (error) {
                    this.clientError = "Connection error. Try again.";
                }
                this.submitting = false;
            }
        }'>
        <section class="relative hidden items-center justify-center overflow-hidden bg-gray-900 lg:flex lg:w-1/2" aria-label="FixTrack">
            <img src="{{ asset('sign-in-repair.jpg') }}" alt="" class="absolute inset-0 size-full object-cover">
            <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/40 to-black/30"></div>
            <div class="relative z-10 px-12 text-center text-white drop-shadow-lg">
                <a href="{{ route('home') }}" class="sign-in-brand mb-4 inline-block text-6xl tracking-wide focus-visible:rounded-lg focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-white">Fix Track</a>
                <p class="text-lg font-medium tracking-wide text-gray-200">Online Service Request and Repair Tracking System</p>
            </div>
        </section>

        <section class="relative z-10 flex min-h-dvh w-full flex-col items-center justify-center bg-white px-4 py-8 shadow-2xl sm:px-6 sm:py-12 lg:w-1/2 lg:px-16 lg:shadow-none" aria-labelledby="sign-in-heading">
            <div class="relative z-20 w-full max-w-md">
                <div x-show="! success" x-transition.opacity>
                    <header class="relative mb-8 text-center sm:mb-10">
                        <svg class="pointer-events-none absolute top-1/2 left-1/2 z-0 size-36 -translate-x-1/2 -translate-y-1/2 -rotate-[30deg] text-[#0091D5] opacity-20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.25" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z" /></svg>
                        <h1 id="sign-in-heading" class="relative z-10 mb-3 text-4xl font-bold tracking-tight text-[#0091D5] drop-shadow-sm sm:text-5xl">Sign In</h1>
                        <p class="relative z-10 font-medium text-gray-500">Fix Track</p>
                    </header>
                    <x-auth-session-status class="mb-5" :status="session('status')" />

                    <form method="POST" action="{{ route('login.store') }}" x-on:submit="submitLogin($event)">
                        @csrf
                        <div class="relative mb-8">
                            <label for="email" class="absolute -top-2.5 left-4 z-10 rounded-sm bg-white px-2 text-xs font-bold text-[#0091D5]">Email:</label>
                            <svg class="pointer-events-none absolute top-1/2 left-4 size-5 -translate-y-1/2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                            <input id="email" name="email" type="email" value="{{ old('email') }}" placeholder="juandelacruz@example.com" required autofocus autocomplete="email" aria-invalid="{{ $errors->has('email') ? 'true' : 'false' }}" class="sign-in-input">
                        </div>
                        <div class="relative mb-8">
                            <label for="password" class="absolute -top-2.5 left-4 z-10 rounded-sm bg-white px-2 text-xs font-bold text-[#0091D5]">Password</label>
                            <svg class="pointer-events-none absolute top-1/2 left-4 size-5 -translate-y-1/2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
                            <input id="password" name="password" type="password" x-bind:type="showPassword ? 'text' : 'password'" placeholder="••••••••••••" required autocomplete="current-password" aria-invalid="{{ $errors->has('password') ? 'true' : 'false' }}" class="sign-in-input">
                            <button type="button" x-on:click="showPassword = ! showPassword" x-bind:aria-label="showPassword ? 'Hide password' : 'Show password'" x-bind:aria-pressed="showPassword" class="absolute top-1/2 right-2 grid size-10 -translate-y-1/2 place-items-center rounded-lg text-gray-500 hover:text-[#0091D5] focus-visible:outline-2 focus-visible:outline-[#0091D5]" aria-label="Show password">
                                <svg class="size-5" x-show="! showPassword" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8" aria-hidden="true"><path d="M2.5 12s3.5-5 9.5-5 9.5 5 9.5 5-3.5 5-9.5 5-9.5-5-9.5-5Z" /><circle cx="12" cy="12" r="2.5" /></svg>
                                <svg class="size-5" x-show="showPassword" x-cloak fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8" aria-hidden="true"><path d="m3 3 18 18M10.6 6.2A10.9 10.9 0 0 1 12 6c6 0 9.5 6 9.5 6a17 17 0 0 1-3 3.6M6.2 6.7C3.7 8.1 2.5 12 2.5 12s3.5 6 9.5 6c1.1 0 2.1-.2 3-.5" /><path d="M9.9 9.9a3 3 0 0 0 4.2 4.2" /></svg>
                            </button>
                        </div>
                        <div class="mb-8 flex flex-wrap items-center justify-between gap-3 text-sm">
                            <label class="flex cursor-pointer items-center gap-2 font-medium text-gray-500"><input type="checkbox" name="remember" value="1" @checked(old('remember')) class="size-4 accent-[#0091D5]">Remember me</label>
                            @if (Route::has('password.request'))
                                <a href="{{ route('password.request') }}" class="font-semibold text-gray-500 transition-colors hover:text-[#0091D5]">Forgot your password?</a>
                            @endif
                        </div>
                        <button type="submit" x-bind:disabled="submitting" data-test="login-button" class="mb-8 w-full rounded-xl bg-[#0091D5] py-4 font-bold text-white shadow-xl shadow-[#0091D5]/40 transition-all hover:-translate-y-0.5 hover:bg-[#007AB5] hover:shadow-[#0091D5]/60 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#0091D5] active:scale-[0.98] disabled:cursor-wait disabled:opacity-70"><span x-show="! submitting">Sign In</span><span x-show="submitting" x-cloak>Signing in…</span></button>
                        @if ($errors->any())
                            <div class="mb-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700" role="alert"><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
                        @endif
                        <p x-show="clientError" x-cloak x-text="clientError" class="mb-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700" role="alert"></p>
                    </form>

                    <p class="text-center text-sm font-medium text-gray-600">Don't have an account? <a href="{{ route('register') }}" class="font-bold text-[#0091D5] hover:underline">Register Now</a></p>
                    <div class="mt-7 flex items-center gap-3 text-xs font-semibold uppercase tracking-wide text-gray-400"><span class="h-px flex-1 bg-gray-200"></span>or<span class="h-px flex-1 bg-gray-200"></span></div>
                    <a href="{{ route('auth.google.redirect') }}" data-test="google-login-button" class="mt-5 flex w-full items-center justify-center gap-3 rounded-xl border-2 border-gray-200 bg-white px-4 py-3 font-semibold text-gray-700 shadow-sm transition hover:border-[#0091D5] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#0091D5]">
                        <svg class="size-5" viewBox="0 0 24 24" aria-hidden="true"><path fill="#4285F4" d="M21.35 12.27c0-.79-.07-1.55-.2-2.27H12v4.3h5.24a4.48 4.48 0 0 1-1.94 2.94v2.45h3.14c1.84-1.69 2.91-4.18 2.91-7.42Z" /><path fill="#34A853" d="M12 21.75c2.63 0 4.84-.87 6.45-2.36l-3.14-2.45c-.87.58-1.98.92-3.31.92-2.54 0-4.69-1.72-5.46-4.03H3.3v2.53A9.75 9.75 0 0 0 12 21.75Z" /><path fill="#FBBC05" d="M6.54 13.83A5.86 5.86 0 0 1 6.23 12c0-.64.11-1.27.31-1.83V7.64H3.3A9.75 9.75 0 0 0 2.25 12c0 1.57.38 3.05 1.05 4.36l3.24-2.53Z" /><path fill="#EA4335" d="M12 6.14c1.43 0 2.72.49 3.73 1.45l2.8-2.8C16.84 3.2 14.63 2.25 12 2.25A9.75 9.75 0 0 0 3.3 7.64l3.24 2.53c.77-2.31 2.92-4.03 5.46-4.03Z" /></svg>
                        Continue with Google
                    </a>
                </div>
                <div x-show="success" x-cloak class="py-16 text-center"><div class="mx-auto mb-6 grid size-16 place-items-center rounded-full bg-[#0091D5]/10 text-[#0091D5]"><svg class="size-9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5" /></svg></div><h2 class="text-3xl font-bold text-gray-900" x-text="successTitle">Welcome back</h2><p class="mt-3 text-gray-500">Redirecting you to your dashboard</p></div>
            </div>
            <svg class="pointer-events-none absolute inset-x-0 bottom-0 z-0 h-32 w-full text-[#0091D5] opacity-15" viewBox="0 0 1000 200" preserveAspectRatio="none" fill="currentColor" aria-hidden="true"><path d="M0 200V150h50v-30h50v40h50v-60h50v40h50V80h50v90h50V60h50v70h50V90h50v90h50V70h50v80h50V50h50v110h50v-50h50v30h50V90h50v80h50v-50h50v80Z" /></svg>
        </section>
    </main>
    @persist('toast')<flux:toast.group><flux:toast /></flux:toast.group>@endpersist
    @fluxScripts
</body>
</html>
