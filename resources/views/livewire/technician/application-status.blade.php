@php
    $isRejected = auth()->user()->account_status === \App\Models\User::ACCOUNT_REJECTED;
    $serviceType = old('service_type', $verification?->service_type ?? 'home');
    $selectedServices = old('service_categories', $verification?->service_categories ?? []);
    $servicesByCategory = $serviceCatalog->groupBy('category');
    $initialCategory = $servicesByCategory
        ->first(fn ($services) => $services->contains(fn ($service) => in_array($service->code, $selectedServices, true)))
        ?->first()?->category ?? $servicesByCategory->keys()->first() ?? '';
    $documentsByType = $verification?->documents?->keyBy('type') ?? collect();
@endphp

<div class="relative min-h-[calc(100vh-5rem)] overflow-hidden bg-zinc-50 p-4 dark:bg-zinc-950 sm:p-8">
    <div class="pointer-events-none absolute inset-0 select-none overflow-hidden p-6 opacity-45 blur-[5px]" aria-hidden="true">
        <div class="mx-auto max-w-6xl space-y-6">
            <div class="flex items-center justify-between"><div class="h-10 w-64 rounded-xl bg-zinc-300 dark:bg-zinc-700"></div><div class="h-10 w-32 rounded-full bg-violet-300 dark:bg-violet-800"></div></div>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                @foreach (range(1, 4) as $item)
                    <div class="h-28 rounded-2xl bg-white shadow-sm dark:bg-zinc-900"></div>
                @endforeach
            </div>
            <div class="grid gap-6 lg:grid-cols-[1.5fr_1fr]"><div class="h-80 rounded-2xl bg-white shadow-sm dark:bg-zinc-900"></div><div class="h-80 rounded-2xl bg-white shadow-sm dark:bg-zinc-900"></div></div>
        </div>
    </div>

    <div class="relative z-10 mx-auto flex min-h-[calc(100vh-9rem)] max-w-4xl items-center justify-center py-8">
        <div class="w-full rounded-3xl border border-zinc-200 bg-white/95 p-6 shadow-2xl backdrop-blur sm:p-8 dark:border-white/10 dark:bg-zinc-900/95">
            @if (session('status'))
                <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-200" role="status">{{ session('status') }}</div>
            @endif

            <div class="text-center">
                <span class="mx-auto flex size-14 items-center justify-center rounded-full {{ $isRejected ? 'bg-rose-100 text-rose-600 dark:bg-rose-500/15 dark:text-rose-300' : 'bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-300' }}">
                    <flux:icon :name="$isRejected ? 'exclamation-triangle' : 'clock'" class="size-7" />
                </span>
                <flux:heading size="xl" class="mt-4">{{ $isRejected ? 'Application needs correction' : 'Application under review' }}</flux:heading>
                <flux:text class="mx-auto mt-2 max-w-xl text-zinc-600 dark:text-zinc-300">
                    {{ $isRejected ? 'Your technician account is not active. Review the issue below, correct the application, and resubmit it.' : 'Your email is verified. FixTrack staff must approve your application before the technician workspace becomes active.' }}
                </flux:text>
            </div>

            @if ($isRejected)
                <div class="mt-6 rounded-2xl border border-rose-200 bg-rose-50 p-4 text-rose-900 dark:border-rose-500/30 dark:bg-rose-500/10 dark:text-rose-100">
                    <p class="text-xs font-bold uppercase tracking-wider">Specific issue from the reviewer</p>
                    <p class="mt-2 whitespace-pre-line text-sm">{{ $verification?->decision_reason ?: 'The reviewer did not provide a reason. Contact support before resubmitting.' }}</p>
                </div>

                <form
                    method="POST"
                    action="{{ route('technician.application.resubmit') }}"
                    enctype="multipart/form-data"
                    class="mt-7 space-y-7"
                    x-data="technicianRegistration('technician')"
                    x-init="serviceType = $el.dataset.serviceType; selectedCategory = $el.dataset.initialCategory"
                    data-service-type="{{ $serviceType }}"
                    data-initial-category="{{ $initialCategory }}"
                    data-test="technician-resubmission-form"
                >
                    @csrf
                    @if ($errors->any())
                        <div class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800 dark:border-rose-500/30 dark:bg-rose-500/10 dark:text-rose-100" role="alert">
                            <p class="font-semibold">Please correct the highlighted application fields.</p>
                            <ul class="mt-2 list-disc space-y-1 pl-5">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <section class="space-y-4" aria-labelledby="resubmit-personal-heading">
                        <div>
                            <flux:heading id="resubmit-personal-heading" size="lg">Personal information</flux:heading>
                            <flux:text class="mt-1 text-sm text-zinc-500">Confirm the same identity details used in your technician registration.</flux:text>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <flux:input name="surname" label="Last name" value="{{ old('surname', auth()->user()->surname) }}" autocomplete="family-name" required />
                            <flux:input name="first_name" label="First name" value="{{ old('first_name', auth()->user()->first_name) }}" autocomplete="given-name" required />
                            <flux:input name="middle_name" label="Middle name" value="{{ old('middle_name', auth()->user()->middle_name) }}" autocomplete="additional-name" />
                            <flux:input name="phone" label="Contact number" value="{{ old('phone', $verification?->phone ?? auth()->user()->phone) }}" type="tel" autocomplete="tel" required />
                            <flux:input class="sm:col-span-2" label="Verified email address" value="{{ auth()->user()->email }}" type="email" readonly />
                        </div>
                    </section>

                    <flux:separator />

                    <section class="space-y-4" aria-labelledby="resubmit-service-heading">
                        <div>
                            <flux:heading id="resubmit-service-heading" size="lg">Service profile</flux:heading>
                            <flux:text class="mt-1 text-sm text-zinc-500">Update your address, service setup, experience, and capabilities.</flux:text>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <flux:input name="service_area" label="Complete address / service area" value="{{ old('service_area', $verification?->service_area) }}" required />
                            <flux:input name="years_experience" label="Years of experience" type="number" min="0" max="60" value="{{ old('years_experience', $verification?->years_experience) }}" required />
                            <flux:select name="service_type" label="Service type" x-model="serviceType" required>
                                <flux:select.option value="home">Home service</flux:select.option>
                                <flux:select.option value="walkin">Walk-in</flux:select.option>
                                <flux:select.option value="both">Both</flux:select.option>
                            </flux:select>
                        </div>

                        <div x-show="serviceType === 'walkin' || serviceType === 'both'" x-cloak>
                            <flux:input name="shop_name" label="Shop name" value="{{ old('shop_name', $verification?->shop_name) }}" x-bind:required="serviceType === 'walkin' || serviceType === 'both'" x-bind:disabled="serviceType === 'home'" />
                        </div>

                        <div class="grid gap-4 sm:grid-cols-[minmax(0,0.7fr)_minmax(0,1.3fr)]">
                            <flux:select label="Service category" x-model="selectedCategory">
                                @foreach ($servicesByCategory as $category => $services)
                                    <flux:select.option value="{{ $category }}">{{ $category }} ({{ $services->count() }})</flux:select.option>
                                @endforeach
                            </flux:select>
                            <div class="rounded-xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm text-zinc-600 dark:border-white/10 dark:bg-white/5 dark:text-zinc-300">
                                Select every service you can personally complete. You can switch categories without losing checked services.
                            </div>
                        </div>

                        <fieldset>
                            <legend class="text-sm font-medium text-zinc-800 dark:text-zinc-200">Services you can personally handle</legend>
                            @foreach ($servicesByCategory as $category => $services)
                                <div x-show="selectedCategory === $el.dataset.category" data-category="{{ $category }}" class="mt-3 grid gap-3 sm:grid-cols-2" x-cloak>
                                    @foreach ($services as $service)
                                        <label wire:key="resubmit-service-{{ $service->id }}" class="flex cursor-pointer items-start gap-3 rounded-xl border border-zinc-200 p-3 text-sm transition hover:border-violet-300 hover:bg-violet-50/40 has-checked:border-violet-400 has-checked:bg-violet-50 dark:border-white/10 dark:hover:border-violet-500/50 dark:hover:bg-violet-500/10 dark:has-checked:border-violet-500/60 dark:has-checked:bg-violet-500/10">
                                            <input name="service_categories[]" type="checkbox" value="{{ $service->code }}" class="mt-0.5 rounded border-zinc-300 text-violet-600" @checked(in_array($service->code, $selectedServices, true))>
                                            <span><strong class="block">{{ $service->name }}</strong><span class="text-xs text-zinc-500">{{ $service->description ?: $category }}</span></span>
                                        </label>
                                    @endforeach
                                </div>
                            @endforeach
                            @error('service_categories')<p class="mt-2 text-sm text-rose-600">{{ $message }}</p>@enderror
                            @error('service_categories.*')<p class="mt-2 text-sm text-rose-600">{{ $message }}</p>@enderror
                        </fieldset>
                    </section>

                    <flux:separator />

                    <section class="space-y-4" aria-labelledby="resubmit-documents-heading">
                        <div>
                            <flux:heading id="resubmit-documents-heading" size="lg">Verification documents</flux:heading>
                            <flux:text class="mt-1 text-sm text-zinc-500">Upload a replacement only for documents that need correction. Existing files remain attached when no replacement is selected.</flux:text>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <label for="resubmit-valid-id" class="group flex min-h-32 cursor-pointer flex-col items-center justify-center rounded-2xl border border-dashed border-zinc-300 bg-zinc-50 p-4 text-center transition hover:border-violet-400 hover:bg-violet-50 dark:border-zinc-700 dark:bg-white/[0.03] dark:hover:border-violet-500/60 dark:hover:bg-violet-500/10">
                                <flux:icon name="arrow-up-tray" class="size-6 text-zinc-400 transition group-hover:text-violet-600" />
                                <span class="mt-3 max-w-full truncate text-sm font-semibold" data-current-label="{{ $documentsByType->get('valid_id')?->label ?? 'Upload valid ID' }}" x-text="validIdName || $el.dataset.currentLabel"></span>
                                <span class="mt-1 text-xs text-zinc-500">JPG, PNG, or WEBP image · max 5 MB</span>
                                <input id="resubmit-valid-id" name="valid_id" type="file" accept="image/jpeg,image/png,image/webp" class="sr-only" x-on:change="selectDocument('validId', $event)">
                            </label>

                            <label for="resubmit-credentials" class="group flex min-h-32 cursor-pointer flex-col items-center justify-center rounded-2xl border border-dashed border-zinc-300 bg-zinc-50 p-4 text-center transition hover:border-violet-400 hover:bg-violet-50 dark:border-zinc-700 dark:bg-white/[0.03] dark:hover:border-violet-500/60 dark:hover:bg-violet-500/10">
                                <flux:icon name="document-arrow-up" class="size-6 text-zinc-400 transition group-hover:text-violet-600" />
                                <span class="mt-3 max-w-full truncate text-sm font-semibold" data-current-label="{{ $documentsByType->get('credentials')?->label ?? 'Upload credentials' }}" x-text="credentialsName || $el.dataset.currentLabel"></span>
                                <span class="mt-1 text-xs text-zinc-500">PDF, DOC, or DOCX · max 5 MB</span>
                                <input id="resubmit-credentials" name="credentials" type="file" accept=".pdf,.doc,.docx" class="sr-only" x-on:change="selectDocument('credentials', $event)">
                            </label>
                        </div>

                        <p x-show="validIdError" x-text="validIdError" class="text-sm text-rose-600" role="alert"></p>
                        <p x-show="credentialsError" x-text="credentialsError" class="text-sm text-rose-600" role="alert"></p>
                        @error('valid_id')<p class="text-sm text-rose-600">{{ $message }}</p>@enderror
                        @error('credentials')<p class="text-sm text-rose-600">{{ $message }}</p>@enderror
                    </section>

                    <flux:separator />

                    <flux:textarea name="resubmission_notes" label="What did you correct?" rows="4" placeholder="Explain the changes made for the reviewer." required>{{ old('resubmission_notes') }}</flux:textarea>

                    <div class="flex justify-end">
                        <flux:button type="submit" variant="primary" icon="arrow-path">Resubmit application</flux:button>
                    </div>
                </form>
            @else
                <div class="mt-7 grid gap-3 sm:grid-cols-3">
                    @foreach ([['Email confirmed', true], ['Admin review', false], ['Account activation', false]] as [$label, $complete])
                        <div class="rounded-xl border p-4 text-center {{ $complete ? 'border-emerald-200 bg-emerald-50 dark:border-emerald-500/30 dark:bg-emerald-500/10' : 'border-zinc-200 bg-zinc-50 dark:border-white/10 dark:bg-white/5' }}">
                            <flux:icon :name="$complete ? 'check-circle' : 'clock'" class="mx-auto size-5 {{ $complete ? 'text-emerald-600' : 'text-zinc-400' }}" />
                            <p class="mt-2 text-sm font-medium">{{ $label }}</p>
                        </div>
                    @endforeach
                </div>
                <p class="mt-6 text-center text-sm text-zinc-500">We will email you as soon as the application is approved or if corrections are required.</p>
            @endif
        </div>
    </div>
</div>
