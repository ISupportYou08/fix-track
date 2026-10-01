@props(['content', 'moduleState'])

@php
    $applications = $content['applications'];
    $selectedVerification = $content['selectedVerification'];
    $latestAccountAction = $content['latestAccountAction'];
@endphp

<div class="mx-auto w-full max-w-7xl space-y-6" data-technician-verification-dashboard>
    <header>
        <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">Technician</h1>
        <p class="mt-1 text-base text-slate-500 dark:text-slate-400">Review applications and browse verified technician accounts.</p>
        <span class="sr-only" data-super-admin-module-state="{{ $moduleState['label'] }}">{{ $moduleState['label'] }} · Pending review: {{ $content['pendingCount'] }}</span>
    </header>

    <div class="flex flex-col gap-4 border-b border-slate-200 sm:flex-row sm:items-end sm:justify-between dark:border-white/10" data-technician-control-bar>
        <nav class="-mb-px flex max-w-full gap-6 overflow-x-auto" aria-label="Technician account status">
            <button type="button" wire:click="setVerificationFilter('verified')" aria-current="{{ $this->verificationFilter === 'verified' ? 'page' : 'false' }}" class="flex shrink-0 items-center border-b-2 px-1 py-3 text-base font-medium transition-colors focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-500 {{ $this->verificationFilter === 'verified' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-300' : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200' }}">
                Verified accounts
                <span class="ml-2 rounded-full border border-indigo-100 bg-indigo-50 px-2.5 py-0.5 text-xs font-semibold text-indigo-600 shadow-sm dark:border-indigo-500/20 dark:bg-indigo-500/10 dark:text-indigo-300">{{ $content['verifiedCount'] }}</span>
            </button>
            <button type="button" wire:click="setVerificationFilter('pending')" aria-current="{{ $this->verificationFilter === 'pending' ? 'page' : 'false' }}" class="flex shrink-0 items-center border-b-2 px-1 py-3 text-base font-medium transition-colors focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-500 {{ $this->verificationFilter === 'pending' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-300' : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200' }}">
                Needs verification
                <span class="ml-2 rounded-full border border-rose-200 bg-rose-100 px-2.5 py-0.5 text-xs font-semibold text-rose-700 shadow-sm dark:border-rose-500/20 dark:bg-rose-500/10 dark:text-rose-300">{{ $content['needsVerificationCount'] }}</span>
            </button>
        </nav>

        <div class="relative w-full pb-2 sm:w-80 sm:shrink-0">
            <flux:icon name="magnifying-glass" class="pointer-events-none absolute left-3 top-[calc(50%-0.25rem)] size-4 -translate-y-1/2 text-slate-400" />
            <input
                type="search"
                wire:model.live.debounce.300ms="verificationSearch"
                placeholder="Search by applicant name..."
                aria-label="Search technicians by applicant name"
                class="w-full rounded-lg border border-slate-300 bg-white py-2.5 pl-10 pr-3 text-base text-slate-900 shadow-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500 dark:border-white/10 dark:bg-zinc-900 dark:text-white dark:focus:ring-indigo-500/40"
                data-technician-verification-search
            >
        </div>
    </div>

    <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm dark:border-white/10 dark:bg-zinc-900" aria-label="{{ $this->verificationFilter === 'verified' ? 'Verified technicians' : 'Technicians needing verification' }}">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[700px] text-left text-base">
                <thead class="border-b border-slate-200 bg-slate-50/80 text-xs font-semibold uppercase tracking-wider text-slate-500 dark:border-white/10 dark:bg-white/[0.03] dark:text-slate-400">
                    <tr>
                        <th scope="col" class="px-6 py-4">Applicant</th>
                        <th scope="col" class="px-6 py-4" data-super-admin-column-type="chips">Services</th>
                        <th scope="col" class="px-6 py-4">Service Setup</th>
                        <th scope="col" class="px-6 py-4" data-super-admin-column-type="status">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-white/10">
                    @forelse ($applications as $verification)
                        @php
                            $accountStatus = $verification->technician?->account_status;
                            $statusLabel = match (true) {
                                $verification->status === 'approved' && $accountStatus === 'suspended' => 'Suspended',
                                $verification->status === 'approved' && $accountStatus === 'banned' => 'Banned',
                                $verification->status === 'approved' => 'Verified',
                                $verification->status === 'rejected' => 'Declined',
                                $verification->status === 'information_requested' => 'Resubmit',
                                default => 'Pending',
                            };
                            $statusClasses = match ($statusLabel) {
                                'Verified' => 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-500/20 dark:bg-emerald-500/10 dark:text-emerald-300',
                                'Banned', 'Declined' => 'border-rose-200 bg-rose-50 text-rose-700 dark:border-rose-500/20 dark:bg-rose-500/10 dark:text-rose-300',
                                'Suspended', 'Resubmit' => 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-500/20 dark:bg-amber-500/10 dark:text-amber-300',
                                default => 'border-blue-200 bg-blue-50 text-blue-700 dark:border-blue-500/20 dark:bg-blue-500/10 dark:text-blue-300',
                            };
                            $setupLabel = match ($verification->service_type) {
                                'home' => 'Home Service',
                                'walkin' => 'Walk-in',
                                'both' => 'Walk-in, Home',
                                default => 'Not provided',
                            };
                        @endphp
                        <tr wire:key="verification-{{ $verification->id }}" class="group transition-colors hover:bg-slate-50/80 dark:hover:bg-white/[0.03]">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-4">
                                    @if ($verification->technician?->avatarUrl())
                                        <img src="{{ $verification->technician->avatarUrl() }}" alt="" class="size-10 shrink-0 rounded-full object-cover" />
                                    @else
                                        <span class="grid size-10 shrink-0 place-items-center rounded-full border border-indigo-100 bg-indigo-50 text-sm font-bold text-indigo-600 transition-transform group-hover:scale-105 dark:border-indigo-500/20 dark:bg-indigo-500/10 dark:text-indigo-300" aria-hidden="true">{{ $verification->technician?->initials() ?? '—' }}</span>
                                    @endif
                                    <div class="min-w-0">
                                        <button type="button" wire:click="openVerificationDetails({{ $verification->id }})" class="block max-w-[220px] truncate text-left font-semibold text-slate-900 hover:text-indigo-600 hover:underline focus-visible:rounded focus-visible:outline-2 focus-visible:outline-indigo-500 dark:text-white dark:hover:text-indigo-300" data-technician-details-button>
                                            {{ $verification->technician?->name ?? 'Unknown applicant' }}
                                        </button>
                                        <span class="block max-w-[220px] truncate text-sm text-slate-500 dark:text-slate-400">{{ $verification->technician?->email ?? 'No email' }}</span>
                                    </div>
                                </div>
                            </td>
                            <td class="max-w-[240px] px-6 py-4 text-slate-700 dark:text-slate-300">{{ $verification->service_labels }}</td>
                            <td class="px-6 py-4 text-slate-700 dark:text-slate-300">{{ $setupLabel }}</td>
                            <td class="px-6 py-4">
                                <button type="button" wire:click="openVerificationDetails({{ $verification->id }})" class="inline-flex items-center justify-center rounded-full border px-2.5 py-1 text-xs font-medium shadow-sm transition hover:-translate-y-px hover:shadow focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-500 {{ $statusClasses }}" title="View and manage {{ $verification->technician?->name ?? 'applicant' }}">
                                    {{ $statusLabel }}
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-12 text-center">
                                <div class="mx-auto grid size-16 place-items-center rounded-full bg-gray-100 text-gray-400 dark:bg-white/10">
                                    <flux:icon name="magnifying-glass" class="size-6" />
                                </div>
                                <h2 class="mt-4 text-sm font-medium text-gray-900 dark:text-white">No applicants found</h2>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $this->verificationSearch !== '' ? 'We could not find technicians matching your search.' : ($this->verificationFilter === 'verified' ? 'No verified technician accounts yet.' : 'No technician applications need verification right now.') }}</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-200 bg-slate-50 px-6 py-3 text-sm text-slate-700 dark:border-white/10 dark:bg-white/[0.03] dark:text-slate-300" data-technician-results-footer>
            @if ($applications->hasPages())
                {{ $applications->links() }}
            @else
                <p>Showing <span class="font-medium">{{ $applications->firstItem() ?? 0 }}</span> to <span class="font-medium">{{ $applications->lastItem() ?? 0 }}</span> of <span class="font-medium">{{ $applications->total() }}</span> results</p>
            @endif
        </div>
    </section>

    <flux:modal name="technician-verification-details" class="max-w-lg" wire:model="showVerificationDetails" @close="closeVerificationDetails" data-technician-verification-details>
        @if ($selectedVerification)
            @php
                $selectedAccountStatus = $selectedVerification->technician?->account_status;
                $selectedStatus = match (true) {
                    $selectedVerification->status === 'approved' && $selectedAccountStatus === 'suspended' => 'Suspended',
                    $selectedVerification->status === 'approved' && $selectedAccountStatus === 'banned' => 'Banned',
                    $selectedVerification->status === 'approved' => 'Verified',
                    $selectedVerification->status === 'rejected' => 'Declined',
                    $selectedVerification->status === 'information_requested' => 'Resubmit',
                    default => 'Pending',
                };
                $selectedSetup = match ($selectedVerification->service_type) {
                    'home' => 'Home Service',
                    'walkin' => 'Walk-in',
                    'both' => 'Walk-in, Home',
                    default => 'Not provided',
                };
            @endphp
            <div class="space-y-5">
                <div class="flex items-center justify-between gap-3">
                    <flux:heading size="lg">Technician Details</flux:heading>
                    <flux:button size="sm" variant="ghost" icon="x-mark" aria-label="Close technician details" wire:click="closeVerificationDetails" />
                </div>

                <div class="flex items-center gap-4">
                    @if ($selectedVerification->technician?->avatarUrl())
                        <img src="{{ $selectedVerification->technician->avatarUrl() }}" alt="" class="size-12 shrink-0 rounded-full object-cover" />
                    @else
                        <span class="grid size-12 shrink-0 place-items-center rounded-full bg-violet-100 text-lg font-bold text-violet-700 dark:bg-violet-500/15 dark:text-violet-300" aria-hidden="true">{{ $selectedVerification->technician?->initials() ?? '—' }}</span>
                    @endif
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $selectedVerification->technician?->name ?? 'Unknown applicant' }}</h2>
                        <span class="inline-flex rounded px-2 py-0.5 text-xs font-bold {{ match ($selectedStatus) { 'Verified' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300', 'Banned' => 'bg-rose-50 text-rose-700 dark:bg-rose-500/10 dark:text-rose-300', 'Suspended' => 'bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300', default => 'bg-violet-50 text-violet-700 dark:bg-violet-500/10 dark:text-violet-300' } }}">{{ $selectedStatus }}</span>
                    </div>
                </div>

                <dl class="divide-y divide-gray-200/60 rounded-lg border border-gray-100 bg-gray-50 dark:divide-white/10 dark:border-white/10 dark:bg-white/[0.03]">
                    @foreach ([
                        'Email' => $selectedVerification->technician?->email ?? '—',
                        'Email verification' => $selectedVerification->technician?->email_verified_at ? 'Verified' : 'Not verified',
                        'Phone' => $selectedVerification->phone ?: ($selectedVerification->technician?->phone ?? '—'),
                        'Experience' => $selectedVerification->years_experience.' years',
                        'Location' => $selectedVerification->service_area ?: '—',
                        'Services' => $selectedVerification->service_labels,
                        'Service Setup' => $selectedSetup.($selectedVerification->shop_name ? ' · '.$selectedVerification->shop_name : ''),
                        'Submission Date' => $selectedVerification->submitted_at?->format('M j, Y') ?? '—',
                    ] as $label => $value)
                        <div class="flex flex-col gap-1 p-4 sm:flex-row sm:items-center sm:justify-between">
                            <dt class="text-xs font-bold uppercase tracking-wider text-gray-600 dark:text-gray-300">{{ $label }}</dt>
                            <dd class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $value }}</dd>
                        </div>
                    @endforeach
                </dl>

                @if ($selectedVerification->status === 'approved' && $selectedAccountStatus === 'suspended' && $selectedVerification->technician?->suspended_until)
                    <p class="text-sm font-medium text-amber-700 dark:text-amber-300">Suspended until {{ $selectedVerification->technician->suspended_until->format('M j, Y g:i A T') }}</p>
                @endif

                @if ($selectedVerification->resubmission_count > 0)
                    <p class="text-xs font-semibold text-amber-700 dark:text-amber-300">Resubmitted {{ $selectedVerification->resubmission_count }} {{ $selectedVerification->resubmission_count === 1 ? 'time' : 'times' }}</p>
                @endif
                @if ($selectedVerification->decision_reason)
                    <div class="rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-900 dark:border-amber-500/20 dark:bg-amber-500/10 dark:text-amber-200">
                        <p class="font-semibold">Review decision</p>
                        <p class="mt-1">{{ $selectedVerification->decision_reason }}</p>
                    </div>
                @endif
                @if ($latestAccountAction)
                    <div class="rounded-lg border border-gray-200 bg-gray-50 p-3 text-sm text-gray-800 dark:border-white/10 dark:bg-white/[0.03] dark:text-gray-200">
                        <p class="font-semibold">Latest account action: {{ ucfirst((string) ($latestAccountAction->details['account_status'] ?? 'updated')) }}</p>
                        <p class="mt-1">{{ $latestAccountAction->details['reason'] ?? '—' }}</p>
                    </div>
                @endif
                @if ($selectedVerification->resubmission_notes)
                    <div class="rounded-lg border border-gray-200 p-3 text-sm text-gray-700 dark:border-white/10 dark:text-gray-300">
                        <p class="font-semibold">Applicant correction notes</p>
                        <p class="mt-1">{{ $selectedVerification->resubmission_notes }}</p>
                    </div>
                @endif

                <div>
                    <h3 class="mb-2 text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Attached Documents</h3>
                    <div class="space-y-2">
                        @forelse ($selectedVerification->documents as $document)
                            @if ($document->can_preview)
                                <a href="{{ route(auth()->user()->operationsRouteName('technician-documents.show'), $document) }}" target="_blank" rel="noopener noreferrer" class="flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50 dark:border-white/10 dark:bg-zinc-900 dark:text-gray-200 dark:hover:bg-white/5">
                                    <flux:icon :name="str_ends_with(strtolower($document->file_path), '.pdf') ? 'document-text' : 'photo'" class="size-5 text-violet-600 dark:text-violet-300" />
                                    View {{ $document->label }}
                                </a>
                            @else
                                <p class="rounded-lg border border-gray-200 px-4 py-2 text-sm text-gray-500 dark:border-white/10">File unavailable: {{ $document->label }}</p>
                            @endif
                        @empty
                            <p class="text-sm text-gray-500 dark:text-gray-400">No documents attached.</p>
                        @endforelse
                    </div>
                </div>

                <div class="flex flex-wrap items-center justify-end gap-2 border-t border-gray-100 pt-4 dark:border-white/10">
                    @if (auth()->user()->isStaff())
                        @if ($selectedVerification->status === 'approved')
                            <span class="rounded-lg bg-blue-50 px-3 py-2 text-sm font-semibold text-blue-700 dark:bg-blue-500/10 dark:text-blue-200">Administrator approval required for account restrictions.</span>
                        @else
                            @if ($selectedVerification->status === 'submitted')
                                <flux:button variant="outline" wire:click="updateVerificationStatus({{ $selectedVerification->id }}, 'under_review')">Mark under review</flux:button>
                            @endif
                            @if ($selectedVerification->status !== 'information_requested')
                                <flux:button variant="primary" wire:click="requestConfirmation('request-verification-information', {{ $selectedVerification->id }})">Request resubmission</flux:button>
                            @endif
                            <span class="w-full text-right text-sm text-gray-500 dark:text-gray-400">An Administrator completes the final decision.</span>
                        @endif
                    @elseif ($selectedVerification->status === 'approved')
                        @if ($selectedAccountStatus === 'active')
                            <flux:button variant="outline" wire:click="requestConfirmation('suspend-technician', {{ $selectedVerification->id }})">Suspend account</flux:button>
                            <flux:button variant="danger" wire:click="requestConfirmation('ban-technician', {{ $selectedVerification->id }})">Ban account</flux:button>
                        @elseif ($selectedAccountStatus === 'suspended')
                            <flux:button variant="outline" wire:click="requestConfirmation('restore-technician', {{ $selectedVerification->id }})">Restore account</flux:button>
                            <flux:button variant="danger" wire:click="requestConfirmation('ban-technician', {{ $selectedVerification->id }})">Ban account</flux:button>
                        @elseif ($selectedAccountStatus === 'banned')
                            <flux:button variant="primary" wire:click="requestConfirmation('restore-technician', {{ $selectedVerification->id }})">Restore account</flux:button>
                        @endif
                    @else
                        @if ($selectedVerification->status !== 'rejected')
                            <flux:button variant="danger" wire:click="requestConfirmation('reject-verification', {{ $selectedVerification->id }})">Decline</flux:button>
                        @endif
                        @if ($selectedVerification->status !== 'information_requested')
                            <flux:button variant="outline" wire:click="requestConfirmation('request-verification-information', {{ $selectedVerification->id }})">Resubmit</flux:button>
                        @endif
                        @if ($selectedVerification->technician?->email_verified_at !== null)
                            <flux:button variant="primary" wire:click="requestConfirmation('approve-verification', {{ $selectedVerification->id }})">Accept</flux:button>
                        @endif
                    @endif
                </div>
            </div>
        @endif
    </flux:modal>
</div>
