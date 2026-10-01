<x-layouts::auth :title="__('Register')">
    @php
        $googleRegistration = $googleRegistration ?? null;
        $selectedServiceCategories = old('service_categories');
        if (! is_array($selectedServiceCategories)) {
            $legacyServiceCategory = old('service_category');
            $selectedServiceCategories = $legacyServiceCategory ? [$legacyServiceCategory] : [];
        }
    @endphp

    <div x-data="technicianRegistration(@js(old('role')), @js(old('service_type', 'both')))">
        <section class="registration-role-page fixed inset-0 z-50 flex min-h-screen items-center justify-center overflow-y-auto p-4 md:p-8" x-show="selectingRole" x-cloak data-test="registration-role-selector">
            <div class="registration-geometric-lines" aria-hidden="true"></div>

            <main class="registration-glass relative z-10 mx-auto w-full max-w-4xl rounded-[2.5rem] border-2 border-slate-900 px-6 py-12 text-center shadow-[0_12px_40px_rgba(0,0,0,0.15)] md:px-12 md:py-16">
                <a href="{{ route('home') }}" class="mb-6 inline-flex rounded-2xl border border-gray-100 bg-white p-3 shadow-lg focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600" aria-label="{{ __('FixTrack home') }}">
                    <img src="{{ asset('fixtrack-logo.png') }}" alt="" class="size-10 object-contain">
                </a>

                <h1 class="text-3xl font-bold text-slate-900 drop-shadow-sm md:text-4xl">{{ __('Create your account') }}</h1>
                <p class="mt-3 text-base font-medium text-slate-700">{{ __('Choose how you will use FixTrack.') }}</p>

                @if ($googleRegistration)
                    <div class="mx-auto mt-6 flex max-w-xl items-center justify-center gap-3 rounded-2xl border border-blue-200 bg-blue-50/90 px-4 py-3 text-sm text-blue-900" data-test="google-registration-notice">
                        <svg class="size-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M20 6 9 17l-5-5" /></svg>
                        <span>{{ __('Google verified :email. Choose an account type to finish registration.', ['email' => $googleRegistration['email']]) }}</span>
                    </div>
                @endif

                <x-auth-session-status class="mt-6" :status="session('status')" />

                @error('role')
                    <div class="mx-auto mt-6 max-w-2xl rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700" role="alert">{{ $message }}</div>
                @enderror

                <div class="mx-auto mt-12 grid max-w-2xl grid-cols-1 gap-6 md:grid-cols-2">
                    <button type="button" class="registration-role-card group flex w-full flex-col items-center justify-center rounded-3xl border-2 border-transparent bg-white p-10 text-center shadow-xl" x-on:click="chooseRole('customer')">
                        <span class="mb-6 flex size-20 items-center justify-center rounded-2xl bg-blue-50 text-blue-600 shadow-sm transition duration-300 group-hover:scale-105 group-hover:bg-blue-100" aria-hidden="true">
                            <svg class="size-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 1 1-8 0 4 4 0 0 1 8 0ZM12 14a7 7 0 0 0-7 7h14a7 7 0 0 0-7-7Z" /></svg>
                        </span>
                        <span class="text-2xl font-bold text-gray-900">{{ __('Customer') }}</span>
                        <span class="mx-auto mt-2 max-w-44 text-sm text-gray-500">{{ __('Book and track home services easily.') }}</span>
                    </button>

                    <button type="button" class="registration-role-card group flex w-full flex-col items-center justify-center rounded-3xl border-2 border-transparent bg-white p-10 text-center shadow-xl" x-on:click="chooseRole('technician')">
                        <span class="mb-6 flex size-20 items-center justify-center rounded-2xl bg-blue-50 text-blue-600 shadow-sm transition duration-300 group-hover:scale-105 group-hover:bg-blue-100" aria-hidden="true">
                            <svg class="size-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 0 0 2.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 0 0 1.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 0 0-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 0 0-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 0 0-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 0 0-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 0 0 1.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065Z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg>
                        </span>
                        <span class="text-2xl font-bold text-gray-900">{{ __('Technician') }}</span>
                        <span class="mx-auto mt-2 max-w-44 text-sm text-gray-500">{{ __('Apply to receive service jobs near you.') }}</span>
                    </button>
                </div>

                <p class="mt-8 text-sm font-medium text-slate-700">{{ __('Already have an account?') }} <a href="{{ route('login') }}" class="font-bold text-blue-700 hover:underline focus-visible:rounded focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">{{ __('Sign in') }}</a></p>
            </main>
        </section>

        <x-customer-registration-form :$googleRegistration />
        <x-technician-registration-form :$serviceCatalog :$selectedServiceCategories :$googleRegistration />
    </div>
</x-layouts::auth>
