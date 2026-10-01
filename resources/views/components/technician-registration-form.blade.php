@props([
    'selectedServiceCategories' => [],
    'serviceCatalog',
    'googleRegistration' => null,
])

@php
    $servicesByCategory = $serviceCatalog->groupBy('category');
    $initialCategory = $servicesByCategory
        ->first(fn ($services) => $services->contains(fn ($service) => in_array($service->code, $selectedServiceCategories, true)))
        ?->first()?->category ?? '';
@endphp

<form
    x-show="role === 'technician' && ! selectingRole"
    x-cloak
    x-init="selectedCategory = $el.dataset.initialCategory"
    method="POST"
    action="{{ $googleRegistration ? route('auth.google.register.store') : route('technician.registration.store') }}"
    enctype="multipart/form-data"
    class="fixed inset-0 z-50 flex min-h-dvh items-center justify-center overflow-hidden bg-gray-900 p-4 font-sans text-gray-800 sm:p-6"
    data-technician-registration-form
    data-initial-category="{{ $initialCategory }}"
    x-on:submit="submitting = true"
>
    @csrf
    <input type="hidden" name="role" value="technician">

    <img
        src="https://images.unsplash.com/photo-1581092160607-ee22621dd758?auto=format&fit=crop&w=2000&q=80"
        alt=""
        class="absolute inset-0 h-full w-full object-cover brightness-[0.7]"
    >

    <div class="technician-registration-scrollbar relative z-10 flex max-h-[92vh] w-full max-w-2xl flex-col overflow-y-auto rounded-[2.5rem] border border-white/70 bg-white/80 p-6 shadow-2xl backdrop-blur-md transition-all duration-500 sm:p-10">
        <div class="mb-6 flex shrink-0 flex-col items-center">
            <div class="relative flex w-full items-center justify-center">
                <button type="button" x-on:click="changeRole()" class="absolute left-0 flex size-10 items-center justify-center rounded-full border border-gray-200 bg-white/80 text-gray-600 shadow-sm transition hover:border-gray-300 hover:bg-white hover:text-[#0070f3] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#0070f3]" aria-label="Back to account type selection" title="Back to account type selection" data-technician-registration-back>
                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m15 18-6-6 6-6M9 12h12"/></svg>
                </button>
                <div class="flex items-center gap-2 text-2xl font-black tracking-wide text-[#0070f3]">
                    <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M14.7 6.3a4 4 0 0 0-5-5L7.4 3.6l3 3L12.7 4a4 4 0 0 0 2 5.3l-9.4 9.4a2.1 2.1 0 0 0 3 3l9.4-9.4a4 4 0 0 0 5.3-2l-2.6 2.3-3-3 2.3-2.3a4 4 0 0 0-5-.9Z"/></svg>
                    <span>FixTrack</span>
                </div>
            </div>
            <h1 class="mt-1 text-lg font-bold text-gray-800">{{ __('Join as Technician') }}</h1>
        </div>

        @if ($errors->any() && old('role') === 'technician')
            <div class="mb-5 rounded-2xl border border-red-200 bg-red-50/95 p-4 text-sm text-red-700" role="alert">
                <p class="font-bold">{{ __('Please review the highlighted fields.') }}</p>
                <ul class="mt-2 list-disc space-y-1 pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div class="flex flex-col">
                <label for="technician-surname" class="mb-1 ml-2 text-xs font-bold text-gray-800">{{ __('Last Name:') }}</label>
                <input id="technician-surname" name="surname" type="text" value="{{ old('surname', $googleRegistration['surname'] ?? '') }}" placeholder="e.g. Smith" required autocomplete="family-name" class="h-12 rounded-full border bg-white/90 px-4 text-sm shadow-md outline-none transition focus:ring-2 focus:ring-[#0070f3]/50 @error('surname') border-red-400 @else border-gray-300/80 @enderror">
                @error('surname')<p class="mt-1 ml-2 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <div class="flex flex-col">
                <label for="technician-first-name" class="mb-1 ml-2 text-xs font-bold text-gray-800">{{ __('First Name:') }}</label>
                <input id="technician-first-name" name="first_name" type="text" value="{{ old('first_name', $googleRegistration['first_name'] ?? '') }}" placeholder="e.g. John" required autocomplete="given-name" x-ref="technicianFirstName" class="h-12 rounded-full border bg-white/90 px-4 text-sm shadow-md outline-none transition focus:ring-2 focus:ring-[#0070f3]/50 @error('first_name') border-red-400 @else border-gray-300/80 @enderror">
                @error('first_name')<p class="mt-1 ml-2 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <div class="flex flex-col">
                <label for="technician-middle-name" class="mb-1 ml-2 text-xs font-bold text-gray-800">{{ __('Middle Name:') }}</label>
                <input id="technician-middle-name" name="middle_name" type="text" value="{{ old('middle_name') }}" placeholder="e.g. Doe" autocomplete="additional-name" class="h-12 rounded-full border bg-white/90 px-4 text-sm shadow-md outline-none transition focus:ring-2 focus:ring-[#0070f3]/50 @error('middle_name') border-red-400 @else border-gray-300/80 @enderror">
                @error('middle_name')<p class="mt-1 ml-2 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <div class="flex flex-col">
                <label for="technician-phone" class="mb-1 ml-2 text-xs font-bold text-gray-800">{{ __('Contact No.:') }}</label>
                <input id="technician-phone" name="phone" type="tel" value="{{ old('phone') }}" placeholder="e.g. +63 900 000 0000" required autocomplete="tel" class="h-12 rounded-full border bg-white/90 px-4 text-sm shadow-md outline-none transition focus:ring-2 focus:ring-[#0070f3]/50 @error('phone') border-red-400 @else border-gray-300/80 @enderror">
                @error('phone')<p class="mt-1 ml-2 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <div class="flex flex-col sm:col-span-2">
                <label for="technician-email" class="mb-1 ml-2 text-xs font-bold text-gray-800">{{ __('Email Address:') }}</label>
                <input id="technician-email" name="email" type="email" value="{{ old('email', $googleRegistration['email'] ?? '') }}" placeholder="john.doe@example.com" required autocomplete="email" @readonly($googleRegistration) class="h-12 rounded-full border px-4 text-sm shadow-md outline-none transition focus:ring-2 focus:ring-[#0070f3]/50 @if ($googleRegistration) bg-gray-100 text-gray-600 @else bg-white/90 @endif @error('email') border-red-400 @else border-gray-300/80 @enderror">
                @if ($googleRegistration)<p class="mt-1 ml-2 text-xs text-emerald-700">{{ __('Verified by Google') }}</p>@endif
                @error('email')<p class="mt-1 ml-2 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <div class="flex flex-col sm:col-span-2">
                <label for="technician-service-area" class="mb-1 ml-2 text-xs font-bold text-gray-800">{{ __('Address:') }}</label>
                <div class="relative">
                    <input id="technician-service-area" name="service_area" type="text" value="{{ old('service_area') }}" placeholder="Complete address" required data-technician-service-area class="h-12 w-full rounded-full border bg-white/90 px-4 pr-12 text-sm shadow-md outline-none transition focus:ring-2 focus:ring-[#0070f3]/50 @error('service_area') border-red-400 @else border-gray-300/80 @enderror">
                    <button type="button" data-technician-location-button class="absolute inset-y-0 right-1 flex w-11 items-center justify-center text-gray-500 transition hover:text-[#0070f3]" title="{{ __('Use my location') }}" aria-label="{{ __('Use my location') }}">
                        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="12" cy="12" r="3"/><circle cx="12" cy="12" r="8"/><path d="M12 2v2M12 20v2M2 12h2M20 12h2"/></svg>
                    </button>
                </div>
                <p class="mt-1 ml-2 hidden text-xs text-gray-600" data-technician-location-status aria-live="polite"></p>
                @error('service_area')<p class="mt-1 ml-2 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <fieldset class="flex flex-col sm:col-span-2">
                <legend class="mb-1 ml-2 text-xs font-bold text-gray-800">{{ __('Service Type:') }}</legend>
                <div class="flex h-12 rounded-full border border-gray-300/80 bg-white/90 p-1 shadow-md">
                    @foreach (['home' => __('Home Service'), 'walkin' => __('Walk-In'), 'both' => __('Both')] as $value => $label)
                        <label class="flex-1 cursor-pointer">
                            <input type="radio" name="service_type" value="{{ $value }}" class="peer sr-only" x-model="serviceType" @checked(old('service_type', 'both') === $value)>
                            <span class="flex h-full items-center justify-center rounded-full text-xs font-bold text-gray-700 transition peer-checked:bg-gray-200 peer-checked:shadow-sm">{{ $label }}</span>
                        </label>
                    @endforeach
                </div>
                @error('service_type')<p class="mt-1 ml-2 text-xs text-red-600">{{ $message }}</p>@enderror
            </fieldset>

            <div class="flex flex-col sm:col-span-2">
                <label for="technician-shop-name" class="mb-1 ml-2 text-xs font-bold text-gray-800">{{ __('Shop Name:') }}</label>
                <input id="technician-shop-name" name="shop_name" type="text" value="{{ old('shop_name') }}" x-bind:disabled="serviceType === 'home'" x-bind:required="serviceType === 'walkin' || serviceType === 'both'" x-bind:placeholder="serviceType === 'home' ? 'Shop or business name (Locked for Home Service)' : 'Enter your shop or business name'" autocomplete="organization" class="h-12 rounded-full border border-gray-300/80 px-4 text-sm transition focus:ring-2 focus:ring-[#0070f3]/50 disabled:cursor-not-allowed disabled:bg-gray-100/80 disabled:text-gray-400 disabled:shadow-inner enabled:bg-white/90 enabled:shadow-md @error('shop_name') border-red-400 @enderror">
                @error('shop_name')<p class="mt-1 ml-2 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <div class="flex flex-col sm:col-span-2">
                <label for="technician-category" class="mb-1 ml-2 text-xs font-bold text-gray-800">{{ __('Select Category:') }}</label>
                <div class="relative">
                    <select id="technician-category" x-model="selectedCategory" class="h-12 w-full cursor-pointer appearance-none rounded-full border border-gray-300/80 bg-white/90 px-4 text-sm shadow-md outline-none transition focus:ring-2 focus:ring-[#0070f3]/50">
                        <option value="">{{ __('Select category...') }}</option>
                        @foreach ($servicesByCategory as $category => $services)
                            <option value="{{ $category }}">{{ $category }} ({{ $services->count() }} {{ \Illuminate\Support\Str::plural('Service', $services->count()) }})</option>
                        @endforeach
                    </select>
                    <svg class="pointer-events-none absolute right-4 top-1/2 size-4 -translate-y-1/2 text-gray-500" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.168l3.71-3.938a.75.75 0 1 1 1.08 1.04l-4.25 4.5a.75.75 0 0 1-1.08 0l-4.25-4.5a.75.75 0 0 1 .02-1.06Z" clip-rule="evenodd"/></svg>
                </div>
            </div>

            <div class="flex flex-col sm:col-span-2" x-show="selectedCategory" x-transition.opacity x-cloak>
                <label class="mb-1 ml-2 text-xs font-bold text-gray-800">{{ __('What work can you personally handle?') }}</label>
                @foreach ($servicesByCategory as $category => $services)
                    <div x-show="selectedCategory === @js($category)" class="grid grid-cols-1 gap-2 sm:grid-cols-3">
                        @foreach ($services as $service)
                            <label for="technician-service-{{ $service->code }}" class="flex h-full cursor-pointer items-start gap-2 rounded-xl border border-gray-300/80 bg-white/90 p-2.5 shadow-md transition hover:bg-white has-checked:border-[#0070f3] has-checked:bg-blue-50/90">
                                <input id="technician-service-{{ $service->code }}" name="service_categories[]" type="checkbox" value="{{ $service->code }}" class="mt-0.5 size-3.5 shrink-0 rounded border-gray-300 text-[#0070f3] focus:ring-[#0070f3]" @checked(in_array($service->code, $selectedServiceCategories, true))>
                                <span class="flex flex-col">
                                    <strong class="text-xs leading-tight text-gray-800">{{ $service->name }}</strong>
                                    <span class="mt-0.5 text-xs leading-tight text-gray-500">{{ $service->description ?: $category }}</span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                @endforeach
                @error('service_categories')<p class="mt-2 ml-2 text-xs text-red-600">{{ $message }}</p>@enderror
                @error('service_categories.*')<p class="mt-2 ml-2 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <div class="flex flex-col sm:col-span-2">
                <label for="technician-years-experience" class="mb-1 ml-2 text-xs font-bold text-gray-800">{{ __('Years of Experience:') }}</label>
                <input id="technician-years-experience" name="years_experience" type="number" min="0" max="60" value="{{ old('years_experience') }}" placeholder="e.g. 5" required class="h-12 rounded-full border bg-white/90 px-4 text-sm shadow-md outline-none transition focus:ring-2 focus:ring-[#0070f3]/50 @error('years_experience') border-red-400 @else border-gray-300/80 @enderror">
                @error('years_experience')<p class="mt-1 ml-2 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            @unless ($googleRegistration)
            <div class="flex min-w-0 flex-col" data-technician-password-column>
                <label for="technician-password" class="mb-1 ml-2 text-xs font-bold text-gray-800">{{ __('Password:') }}</label>
                <div class="relative">
                    <input id="technician-password" name="password" x-model="password" x-bind:type="showPassword ? 'text' : 'password'" placeholder="Create a strong password" required autocomplete="new-password" passwordrules="{{ \Illuminate\Validation\Rules\Password::defaults()->toPasswordRulesString() }}" class="h-12 w-full rounded-full border bg-white/90 px-4 pr-12 text-sm shadow-md outline-none transition focus:ring-2 focus:ring-[#0070f3]/50 @error('password') border-red-400 @else border-gray-300/80 @enderror">
                    <button type="button" class="absolute inset-y-0 right-1 flex w-11 items-center justify-center text-gray-500 hover:text-[#0070f3]" x-on:click="showPassword = ! showPassword" aria-label="{{ __('Show or hide password') }}">
                        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M2.5 12s3.5-5 9.5-5 9.5 5 9.5 5-3.5 5-9.5 5-9.5-5-9.5-5Z"/><circle cx="12" cy="12" r="2.5"/></svg>
                    </button>
                </div>
                <p class="mt-1 ml-2 text-xs text-gray-600">{{ __('Strength:') }} <strong x-text="passwordStrengthLabel" x-bind:class="passwordStrengthClass"></strong></p>
                @error('password')<p class="mt-1 ml-2 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <div class="flex min-w-0 flex-col" data-technician-password-confirmation-column>
                <label for="technician-password-confirmation" class="mb-1 ml-2 text-xs font-bold text-gray-800">{{ __('Confirm Password:') }}</label>
                <div class="relative">
                    <input id="technician-password-confirmation" name="password_confirmation" x-model="passwordConfirmation" x-bind:type="showConfirmation ? 'text' : 'password'" placeholder="Repeat password" required autocomplete="new-password" class="h-12 w-full rounded-full border bg-white/90 px-4 pr-12 text-sm shadow-md outline-none transition focus:ring-2 focus:ring-[#0070f3]/50 {{ $errors->has('password_confirmation') || $errors->has('password') ? 'border-red-400' : 'border-gray-300/80' }}">
                    <button type="button" class="absolute inset-y-0 right-1 flex w-11 items-center justify-center text-gray-500 hover:text-[#0070f3]" x-on:click="showConfirmation = ! showConfirmation" aria-label="{{ __('Show or hide password confirmation') }}">
                        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M2.5 12s3.5-5 9.5-5 9.5 5 9.5 5-3.5 5-9.5 5-9.5-5-9.5-5Z"/><circle cx="12" cy="12" r="2.5"/></svg>
                    </button>
                </div>
                <p class="mt-1 ml-2 text-xs" x-bind:class="passwordsMatch ? 'text-emerald-600' : 'text-gray-600'" x-text="passwordsMatch ? 'Passwords match.' : 'Must match your password.'"></p>
                @error('password_confirmation')<p class="mt-1 ml-2 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
            @endunless

            <div class="flex flex-col sm:col-span-2">
                <label class="mb-1 ml-2 text-xs font-bold text-gray-800">{{ __('Requirements:') }}</label>
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <label for="technician-valid-id" class="group flex h-20 w-full cursor-pointer flex-col items-center justify-center rounded-2xl border border-gray-300/80 bg-white/90 shadow-md transition hover:bg-white">
                        <svg class="size-5 text-gray-500 transition group-hover:text-[#0070f3]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M12 16V4m0 0L8 8m4-4 4 4"/><path d="M4 15v4a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1v-4"/></svg>
                        <span class="mt-1 max-w-[90%] truncate text-xs font-bold text-gray-700" x-text="validIdName || 'Upload Valid ID'"></span>
                        <input id="technician-valid-id" name="valid_id" type="file" accept="image/jpeg,image/png,image/webp" required class="hidden" x-on:change="selectDocument('validId', $event)">
                    </label>

                    <label for="technician-credentials" class="group flex h-20 w-full cursor-pointer flex-col items-center justify-center rounded-2xl border border-gray-300/80 bg-white/90 shadow-md transition hover:bg-white">
                        <svg class="size-5 text-gray-500 transition group-hover:text-[#0070f3]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M7 3h7l4 4v14H7z"/><path d="M14 3v5h5M10 13h5M10 17h5"/></svg>
                        <span class="mt-1 max-w-[90%] truncate text-xs font-bold text-gray-700" x-text="credentialsName || 'Upload Credentials'"></span>
                        <input id="technician-credentials" name="credentials" type="file" accept=".pdf,.doc,.docx" required class="hidden" x-on:change="selectDocument('credentials', $event)">
                    </label>
                </div>
                <p class="mt-2 ml-2 text-xs text-gray-600">{{ __('Valid ID: JPG, PNG, or WEBP image · Credentials: PDF, DOC, or DOCX · maximum 5 MB for each file.') }}</p>
                <p x-show="validIdError" x-text="validIdError" class="mt-1 ml-2 text-xs text-red-600" role="alert"></p>
                <p x-show="credentialsError" x-text="credentialsError" class="mt-1 ml-2 text-xs text-red-600" role="alert"></p>
                @error('valid_id')<p class="mt-1 ml-2 text-xs text-red-600">{{ $message }}</p>@enderror
                @error('credentials')<p class="mt-1 ml-2 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <div class="flex flex-col sm:col-span-2">
                <label for="technician-profile-photo" class="mb-1 ml-2 text-xs font-bold text-gray-800">{{ __('Profile Photo (optional):') }}</label>
                <input id="technician-profile-photo" name="profile_photo" type="file" accept="image/jpeg,image/png,image/webp" class="rounded-xl border border-gray-300/80 bg-white/90 p-3 text-sm shadow-md">
                <p class="mt-1 ml-2 text-xs text-gray-600">{{ __('JPG, PNG, or WEBP · maximum 5 MB') }}</p>
                @error('profile_photo')<p class="mt-1 ml-2 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <div class="mt-2 flex flex-col gap-3 sm:col-span-2">
                <div class="rounded-2xl border border-amber-200 bg-amber-50/90 p-3 text-xs leading-relaxed text-amber-900">
                    {{ $googleRegistration
                        ? __('Google has verified your email. FixTrack staff will review your documents before activating your technician account.')
                        : __('Confirm your email with the 6-digit code we send. FixTrack staff will then review your documents before activating your technician account.') }}
                </div>
                <button type="submit" class="flex h-12 w-full items-center justify-center rounded-xl border border-gray-300/80 bg-white/95 px-8 text-sm font-bold text-gray-800 shadow-lg transition-all hover:-translate-y-0.5 hover:bg-white hover:shadow-xl active:scale-95 disabled:cursor-wait disabled:opacity-70 sm:rounded-full" x-bind:disabled="submitting" data-test="register-technician-button">
                    <span x-show="! submitting">{{ $googleRegistration ? __('Submit application with Google') : __('Submit') }}</span>
                    <span x-show="submitting" x-cloak>{{ __('Submitting…') }}</span>
                </button>
                <div class="mt-1 text-center text-xs text-gray-600">
                    {{ __('I already have an account?') }}
                    <a href="{{ route('login') }}" class="font-bold text-[#0070f3] hover:underline">{{ __('Sign-In') }}</a>
                </div>
            </div>
        </div>
    </div>
</form>
