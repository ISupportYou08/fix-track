@props(['googleRegistration' => null])

<form
    x-show="role === 'customer' && ! selectingRole"
    x-cloak
    method="POST"
    action="{{ $googleRegistration ? route('auth.google.register.store') : route('register.store') }}"
    enctype="multipart/form-data"
    class="fixed inset-0 z-50 flex min-h-dvh items-center justify-center overflow-y-auto bg-slate-50 p-3 font-sans text-slate-900 sm:p-6"
    data-customer-registration-form
    x-on:submit="submitting = true"
>
    @csrf
    <input type="hidden" name="role" value="customer">
    <img src="{{ asset('customer-registration-abstract.jpg') }}" alt="" aria-hidden="true" class="pointer-events-none absolute inset-0 size-full scale-[1.03] object-cover object-center opacity-90 blur-[3px]">
    <div class="pointer-events-none absolute inset-0 bg-gradient-to-br from-white/35 via-white/15 to-slate-100/35" aria-hidden="true"></div>

    <div class="customer-registration-scrollbar relative z-10 flex max-h-[95dvh] w-full max-w-xl flex-col overflow-y-auto rounded-[1.75rem] border border-white/80 bg-white/85 p-5 shadow-[0_24px_80px_-24px_rgba(15,23,42,0.28)] backdrop-blur-2xl sm:p-8">
        <div class="mb-7 flex shrink-0 items-center justify-between">
            <button type="button" x-on:click="changeRole()" class="flex size-10 items-center justify-center rounded-full border border-slate-200 bg-white/80 text-slate-600 shadow-sm transition hover:border-slate-300 hover:bg-white hover:text-slate-900 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600" aria-label="Choose a different account type">
                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m15 18-6-6 6-6M9 12h12" /></svg>
            </button>
            <div class="flex items-center gap-2">
                <span class="flex size-8 items-center justify-center rounded-lg bg-blue-600 text-white shadow-md shadow-blue-600/20" aria-hidden="true">
                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M14.7 6.3a4 4 0 0 0-5-5L7.4 3.6l3 3L12.7 4a4 4 0 0 0 2 5.3l-9.4 9.4a2.1 2.1 0 0 0 3 3l9.4-9.4a4 4 0 0 0 5.3-2l-2.6 2.3-3-3 2.3-2.3a4 4 0 0 0-5-.9Z" /></svg>
                </span>
                <span class="text-xl font-bold tracking-tight text-slate-900">FixTrack</span>
            </div>
            <span class="size-10" aria-hidden="true"></span>
        </div>

        <div class="mb-7 shrink-0 text-center">
            <p class="mb-2 text-xs font-bold uppercase tracking-[0.22em] text-blue-600">Join FixTrack</p>
            <h1 class="text-3xl font-bold tracking-tight text-slate-950">Create your account</h1>
            <p class="mt-2 text-sm leading-6 text-slate-600">Book trusted repair services and track every step in one place.</p>
        </div>

        @if ($errors->any() && old('role') === 'customer')
            <div class="mb-5 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800" role="alert">
                <p class="font-semibold">Please review the highlighted fields.</p>
                <ul class="mt-2 list-disc space-y-1 pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
            <div class="flex flex-col">
                <label for="customer-surname" class="mb-1.5 text-sm font-semibold text-slate-700">Last Name</label>
                <input id="customer-surname" name="surname" type="text" value="{{ old('surname', $googleRegistration['surname'] ?? '') }}" placeholder="e.g. Smith" required autocomplete="family-name" class="customer-registration-input @error('surname') !border-red-400 @enderror" aria-invalid="{{ $errors->has('surname') ? 'true' : 'false' }}">
                @error('surname')<p class="mt-1 text-xs text-red-700">{{ $message }}</p>@enderror
            </div>
            <div class="flex flex-col">
                <label for="customer-first-name" class="mb-1.5 text-sm font-semibold text-slate-700">First Name</label>
                <input id="customer-first-name" name="first_name" type="text" value="{{ old('first_name', $googleRegistration['first_name'] ?? '') }}" placeholder="e.g. John" required autocomplete="given-name" x-ref="customerName" class="customer-registration-input @error('first_name') !border-red-400 @enderror" aria-invalid="{{ $errors->has('first_name') ? 'true' : 'false' }}">
                @error('first_name')<p class="mt-1 text-xs text-red-700">{{ $message }}</p>@enderror
            </div>
            <div class="flex flex-col">
                <label for="customer-middle-name" class="mb-1.5 text-sm font-semibold text-slate-700">Middle Name <span class="font-normal text-slate-500">(optional)</span></label>
                <input id="customer-middle-name" name="middle_name" type="text" value="{{ old('middle_name') }}" placeholder="e.g. Doe" autocomplete="additional-name" class="customer-registration-input @error('middle_name') !border-red-400 @enderror" aria-invalid="{{ $errors->has('middle_name') ? 'true' : 'false' }}">
                @error('middle_name')<p class="mt-1 text-xs text-red-700">{{ $message }}</p>@enderror
            </div>
            <div class="flex flex-col">
                <label for="customer-phone" class="mb-1.5 text-sm font-semibold text-slate-700">Contact No.</label>
                <input id="customer-phone" name="phone" type="tel" value="{{ old('phone') }}" placeholder="e.g. 0912 345 6789" required autocomplete="tel" class="customer-registration-input @error('phone') !border-red-400 @enderror" aria-invalid="{{ $errors->has('phone') ? 'true' : 'false' }}">
                @error('phone')<p class="mt-1 text-xs text-red-700">{{ $message }}</p>@enderror
            </div>
            <div class="flex flex-col sm:col-span-2">
                <label for="customer-email" class="mb-1.5 text-sm font-semibold text-slate-700">Email Address</label>
                <input id="customer-email" name="email" type="email" value="{{ old('email', $googleRegistration['email'] ?? '') }}" placeholder="john.smith@example.com" required autocomplete="email" @readonly($googleRegistration) class="customer-registration-input @if ($googleRegistration) bg-slate-100 text-slate-600 @endif @error('email') !border-red-400 @enderror" aria-invalid="{{ $errors->has('email') ? 'true' : 'false' }}">
                @if ($googleRegistration)<p class="mt-1 text-xs text-emerald-700">{{ __('Verified by Google') }}</p>@endif
                @error('email')<p class="mt-1 text-xs text-red-700">{{ $message }}</p>@enderror
            </div>
            <div class="flex flex-col sm:col-span-2">
                <label for="customer-address" class="mb-1.5 text-sm font-semibold text-slate-700">Complete Address</label>
                <input id="customer-address" name="address" type="text" value="{{ old('address') }}" placeholder="House No., Street, City, Province" required autocomplete="street-address" class="customer-registration-input @error('address') !border-red-400 @enderror" aria-invalid="{{ $errors->has('address') ? 'true' : 'false' }}">
                @error('address')<p class="mt-1 text-xs text-red-700">{{ $message }}</p>@enderror
            </div>
            <div class="flex flex-col sm:col-span-2">
                <label for="customer-profile-photo" class="mb-1.5 text-sm font-semibold text-slate-700">Profile Photo <span class="font-normal text-slate-500">(optional)</span></label>
                <input id="customer-profile-photo" name="profile_photo" type="file" accept="image/jpeg,image/png,image/webp" class="customer-registration-input file:mr-3 file:rounded-lg file:border-0 file:bg-blue-50 file:px-3 file:py-1 file:font-semibold file:text-blue-700 @error('profile_photo') !border-red-400 @enderror" aria-describedby="customer-profile-photo-help">
                <p id="customer-profile-photo-help" class="mt-1 text-xs text-slate-500">JPG, PNG, or WEBP · maximum 5 MB</p>
                @error('profile_photo')<p class="mt-1 text-xs text-red-700">{{ $message }}</p>@enderror
            </div>
            @unless ($googleRegistration)
            <div class="flex flex-col sm:col-span-2">
                <label for="customer-password" class="mb-1.5 text-sm font-semibold text-slate-700">Password</label>
                <div class="relative">
                    <input id="customer-password" name="password" x-model="password" x-bind:type="showPassword ? 'text' : 'password'" placeholder="••••••••" required autocomplete="new-password" passwordrules="{{ \Illuminate\Validation\Rules\Password::defaults()->toPasswordRulesString() }}" class="customer-registration-input !pr-12 @error('password') !border-red-400 @enderror" aria-invalid="{{ $errors->has('password') ? 'true' : 'false' }}">
                    <button type="button" x-on:click="showPassword = ! showPassword" class="absolute inset-y-0 right-0 flex w-12 items-center justify-center text-slate-500 transition hover:text-slate-900 focus-visible:outline-2 focus-visible:outline-blue-600" aria-label="Toggle password visibility">
                        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M2.5 12s3.5-5 9.5-5 9.5 5 9.5 5-3.5 5-9.5 5-9.5-5-9.5-5Z"/><circle cx="12" cy="12" r="2.5"/></svg>
                    </button>
                </div>
                <p class="mt-1 text-xs text-slate-500">Password strength: <strong x-text="passwordStrengthLabel" class="text-slate-700"></strong></p>
                @error('password')<p class="mt-1 text-xs text-red-700">{{ $message }}</p>@enderror
            </div>
            <div class="flex flex-col sm:col-span-2">
                <label for="customer-password-confirmation" class="mb-1.5 text-sm font-semibold text-slate-700">Confirm Password</label>
                <div class="relative">
                    <input id="customer-password-confirmation" name="password_confirmation" x-bind:type="showConfirmation ? 'text' : 'password'" placeholder="••••••••" required autocomplete="new-password" class="customer-registration-input !pr-12 @error('password_confirmation') !border-red-400 @enderror" aria-invalid="{{ $errors->has('password_confirmation') ? 'true' : 'false' }}">
                    <button type="button" x-on:click="showConfirmation = ! showConfirmation" class="absolute inset-y-0 right-0 flex w-12 items-center justify-center text-slate-500 transition hover:text-slate-900 focus-visible:outline-2 focus-visible:outline-blue-600" aria-label="Toggle confirmation visibility">
                        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M2.5 12s3.5-5 9.5-5 9.5 5 9.5 5-3.5 5-9.5 5-9.5-5-9.5-5Z"/><circle cx="12" cy="12" r="2.5"/></svg>
                    </button>
                </div>
                @error('password_confirmation')<p class="mt-1 text-xs text-red-700">{{ $message }}</p>@enderror
            </div>
            @endunless
        </div>

        <div class="mt-8 flex shrink-0 flex-col gap-4">
            <button type="submit" class="flex w-full items-center justify-center rounded-xl border border-blue-600 bg-blue-600 px-8 py-3.5 text-base font-bold text-white shadow-lg shadow-blue-600/20 transition hover:-translate-y-0.5 hover:bg-blue-700 hover:shadow-blue-600/30 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600 active:scale-[0.99] disabled:cursor-wait disabled:opacity-70" x-bind:disabled="submitting" data-test="register-user-button">
                <span x-show="! submitting">{{ $googleRegistration ? __('Complete Google registration') : __('Create account') }}</span>
                <span x-show="submitting" x-cloak>Registering…</span>
            </button>
            <p class="pb-2 text-center text-sm text-slate-600">Already have an account? <a href="{{ route('login') }}" class="font-bold text-blue-700 transition hover:text-blue-800 hover:underline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">Sign in</a></p>
        </div>
    </div>
</form>
