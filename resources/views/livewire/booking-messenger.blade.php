<div wire:poll.10s data-booking-messenger-layout="{{ $compact ? 'compact' : 'full' }}" class="{{ $compact ? 'relative flex h-full min-h-0 overflow-hidden' : 'grid min-h-[32rem] gap-4 lg:grid-cols-[18rem_minmax(0,1fr)]' }}">
    @if ($compact)
        <aside data-booking-chat-rail class="flex w-[4.5rem] shrink-0 flex-col items-center gap-3 border-r border-slate-200/70 bg-gradient-to-b from-indigo-50/80 via-slate-50 to-white py-4 dark:border-white/10 dark:from-indigo-950/50 dark:via-slate-950 dark:to-slate-950">
            <button type="button" wire:click="selectAssistant" aria-label="Open FixTrack Assistant" title="FixTrack Assistant" aria-pressed="{{ $selectedChat === null ? 'true' : 'false' }}" class="flex size-11 shrink-0 items-center justify-center rounded-2xl text-white shadow-sm transition hover:-translate-y-0.5 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-500 {{ $selectedChat === null ? 'bg-gradient-to-br from-violet-500 to-indigo-700 shadow-indigo-500/30 ring-2 ring-indigo-300 ring-offset-2 dark:ring-indigo-400 dark:ring-offset-slate-950' : 'bg-gradient-to-br from-violet-400 to-indigo-600 hover:shadow-md' }}"><flux:icon name="sparkles" class="size-5" /></button>
            <div class="h-px w-8 bg-slate-200 dark:bg-white/10"></div>
            <button type="button" wire:click="toggleContacts" data-booking-chat-add aria-label="Choose a booking contact" aria-expanded="{{ $showContacts ? 'true' : 'false' }}" title="Booking contacts" class="flex size-10 shrink-0 items-center justify-center rounded-2xl border border-slate-200 bg-white text-slate-600 shadow-sm transition hover:-translate-y-0.5 hover:border-indigo-200 hover:text-indigo-600 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-500 dark:border-white/10 dark:bg-white/5 dark:text-slate-200 dark:hover:bg-white/10"><flux:icon name="plus" class="size-5" /></button>
            <div class="min-h-0 w-full flex-1 space-y-3 overflow-y-auto px-2 pt-1" aria-label="Recent conversations">
                @foreach ($chats as $chat)
                    @php
                        $otherUser = $isCustomer ? $chat->booking?->technician : $chat->booking?->customer;
                    @endphp
                    <button type="button" wire:key="compact-chat-head-{{ $chat->id }}" wire:click="selectChat({{ $chat->id }})" data-booking-chat-head="{{ $chat->id }}" aria-label="Open conversation with {{ $otherUser?->name ?: 'Booking contact' }}" aria-pressed="{{ $selectedChat?->id === $chat->id ? 'true' : 'false' }}" title="{{ $otherUser?->name ?: 'Booking contact' }}" class="mx-auto flex size-10 items-center justify-center overflow-hidden rounded-2xl text-sm font-semibold shadow-sm transition hover:-translate-y-0.5 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-500 {{ $selectedChat?->id === $chat->id ? 'bg-slate-900 text-white ring-2 ring-indigo-300 ring-offset-2 dark:bg-indigo-500 dark:ring-indigo-400 dark:ring-offset-slate-950' : 'border border-indigo-100 bg-white text-indigo-700 hover:border-indigo-300 hover:shadow-md dark:border-white/10 dark:bg-white/10 dark:text-indigo-200' }}">
                        @if ($otherUser?->avatarUrl())
                            <img src="{{ $otherUser->avatarUrl() }}" alt="" class="size-full object-cover" />
                        @else
                            {{ mb_substr($otherUser?->name ?: 'T', 0, 1) }}
                        @endif
                    </button>
                @endforeach
            </div>
        </aside>

        @if ($showContacts)
            <div data-booking-contact-picker class="absolute left-[4.5rem] top-24 z-20 max-h-72 w-[min(17rem,calc(100%_-_5rem))] overflow-y-auto rounded-2xl border border-slate-200/80 bg-white/95 p-2 shadow-[0_18px_45px_-18px_rgba(15,23,42,0.4)] backdrop-blur-xl dark:border-white/10 dark:bg-slate-900/95">
                <p class="px-3 pb-2 pt-2 text-xs font-bold uppercase tracking-[0.14em] text-slate-500 dark:text-slate-400">{{ $isCustomer ? 'Accepted booking technicians' : 'Customers from accepted bookings' }}</p>
                @forelse ($chats as $chat)
                    @php
                        $contact = $isCustomer ? $chat->booking?->technician : $chat->booking?->customer;
                    @endphp
                    <button type="button" wire:key="compact-contact-{{ $chat->id }}" wire:click="selectChat({{ $chat->id }})" class="flex w-full items-center justify-between gap-2 rounded-xl px-3 py-2.5 text-left text-sm text-slate-800 transition hover:bg-indigo-50 focus-visible:outline-2 focus-visible:outline-indigo-500 dark:text-white dark:hover:bg-white/10"><span class="truncate font-medium">{{ $contact?->name ?: 'Booking contact' }}</span><span class="shrink-0 text-xs text-slate-500 dark:text-slate-400">{{ $chat->booking?->reference }}</span></button>
                @empty
                    <p class="px-3 py-4 text-sm leading-relaxed text-slate-500 dark:text-slate-400">Contacts appear after a technician accepts your booking.</p>
                @endforelse
            </div>
        @endif
    @else
    <aside class="rounded-[1.75rem] border border-slate-200/80 bg-white p-3 shadow-sm dark:border-white/10 dark:bg-slate-900">
        <div class="flex items-start justify-between gap-2 px-2 pb-4 pt-2">
            <div>
                <flux:heading size="lg" class="tracking-tight">Conversations</flux:heading>
                <flux:text class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ $isCustomer ? 'Your assistant and accepted bookings' : 'Your assistant and customer conversations' }}</flux:text>
            </div>
            <button type="button" wire:click="toggleContacts" aria-label="Choose a booking contact" aria-expanded="{{ $showContacts ? 'true' : 'false' }}" class="flex size-9 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-700 shadow-sm transition hover:border-indigo-200 hover:text-indigo-600 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-500 dark:border-white/10 dark:bg-white/5 dark:text-white dark:hover:bg-white/10"><flux:icon name="plus" class="size-5" /></button>
        </div>

        <div class="space-y-2">
            <button type="button" wire:click="selectAssistant" aria-pressed="{{ $selectedChat === null ? 'true' : 'false' }}" class="flex w-full items-center gap-3 rounded-2xl px-3 py-3 text-left transition focus-visible:outline-2 focus-visible:outline-indigo-500 {{ $selectedChat === null ? 'bg-indigo-50 ring-1 ring-indigo-200 dark:bg-indigo-500/15 dark:ring-indigo-400/25' : 'hover:bg-slate-50 dark:hover:bg-white/5' }}">
                <span class="flex size-10 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-violet-500 to-indigo-700 text-white shadow-sm"><flux:icon name="sparkles" class="size-5" /></span>
                <span class="min-w-0"><span class="block truncate text-sm font-semibold text-slate-900 dark:text-white">FixTrack Assistant</span><span class="block truncate text-xs text-slate-500 dark:text-slate-400">{{ $isCustomer ? 'Booking and service help' : 'Job and account help' }}</span></span>
            </button>

            @if ($showContacts)
                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-2 dark:border-white/10 dark:bg-white/5">
                    <p class="px-2 pb-2 text-xs font-bold uppercase tracking-[0.14em] text-slate-500">{{ $isCustomer ? 'Accepted booking technicians' : 'Customers from accepted bookings' }}</p>
                    @forelse ($chats as $chat)
                        @php
                            $contact = $isCustomer ? $chat->booking?->technician : $chat->booking?->customer;
                        @endphp
                        <button type="button" wire:key="contact-{{ $chat->id }}" wire:click="selectChat({{ $chat->id }})" class="flex w-full items-center justify-between gap-2 rounded-xl px-2 py-2 text-left text-sm transition hover:bg-white focus-visible:outline-2 focus-visible:outline-indigo-500 dark:hover:bg-white/10"><span class="truncate font-medium">{{ $contact?->name ?: 'Booking contact' }}</span><span class="shrink-0 text-xs text-slate-500">{{ $chat->booking?->reference }}</span></button>
                    @empty
                        <p class="px-2 py-3 text-sm text-slate-500 dark:text-slate-400">Contacts appear after a technician accepts your booking.</p>
                    @endforelse
                </div>
            @endif

            @forelse ($chats as $chat)
                @php
                    $otherUser = $isCustomer ? $chat->booking?->technician : $chat->booking?->customer;
                @endphp
                <button type="button" wire:key="chat-head-{{ $chat->id }}" wire:click="selectChat({{ $chat->id }})" aria-pressed="{{ $selectedChat?->id === $chat->id ? 'true' : 'false' }}" class="flex w-full items-center gap-3 rounded-2xl px-3 py-3 text-left transition focus-visible:outline-2 focus-visible:outline-indigo-500 {{ $selectedChat?->id === $chat->id ? 'bg-indigo-50 ring-1 ring-indigo-200 dark:bg-indigo-500/15 dark:ring-indigo-400/25' : 'hover:bg-slate-50 dark:hover:bg-white/5' }}">
                    <span class="flex size-10 shrink-0 items-center justify-center overflow-hidden rounded-2xl bg-indigo-100 text-sm font-semibold text-indigo-700 dark:bg-indigo-500/20 dark:text-indigo-200">
                        @if ($otherUser?->avatarUrl())
                            <img src="{{ $otherUser->avatarUrl() }}" alt="" class="size-full object-cover" />
                        @else
                            {{ mb_substr($otherUser?->name ?: 'T', 0, 1) }}
                        @endif
                    </span>
                    <span class="min-w-0 flex-1"><span class="block truncate text-sm font-semibold text-slate-900 dark:text-white">{{ $otherUser?->name ?: 'Technician' }}</span><span class="block truncate text-xs text-slate-500 dark:text-slate-400">{{ $chat->latestMessage?->body ?: $chat->booking?->reference }}</span></span>
                </button>
            @empty
                @if (! $isCustomer)
                    <p class="px-3 py-8 text-center text-sm text-slate-500 dark:text-slate-400">Accepted bookings will appear here.</p>
                @endif
            @endforelse
        </div>
    </aside>
    @endif

    @php
        $conversationUser = $selectedChat ? ($isCustomer ? $selectedChat->booking?->technician : $selectedChat->booking?->customer) : null;
    @endphp
    <section class="{{ $compact ? 'min-w-0 min-h-0 flex-1' : 'min-h-[32rem] rounded-[1.75rem] border border-slate-200/80 dark:border-white/10' }} flex flex-col overflow-hidden bg-white dark:bg-slate-900">
        <div data-chat-conversation-header class="flex shrink-0 items-center gap-3 border-b border-slate-200/70 bg-white/90 px-4 py-3.5 dark:border-white/10 dark:bg-slate-900/90 {{ $compact ? '' : 'sm:px-6 sm:py-5' }}">
            <span class="flex size-10 shrink-0 items-center justify-center overflow-hidden rounded-2xl text-sm font-semibold shadow-sm {{ $selectedChat ? 'bg-indigo-50 text-indigo-700 ring-1 ring-indigo-100 dark:bg-indigo-500/15 dark:text-indigo-200 dark:ring-indigo-400/20' : 'bg-gradient-to-br from-violet-500 to-indigo-700 text-white' }}">
                @if ($conversationUser?->avatarUrl())
                    <img src="{{ $conversationUser->avatarUrl() }}" alt="" class="size-full object-cover" />
                @elseif ($selectedChat)
                    {{ mb_substr($conversationUser?->name ?: 'T', 0, 1) }}
                @else
                    <flux:icon name="sparkles" class="size-5" />
                @endif
            </span>
            <div class="min-w-0 flex-1">
                <p class="truncate text-sm font-semibold tracking-tight text-slate-950 dark:text-white">{{ $conversationUser?->name ?: 'FixTrack Assistant' }}</p>
                <p class="mt-0.5 truncate text-xs text-slate-500 dark:text-slate-400">{{ $selectedChat ? $selectedChat->booking?->reference.' · '.ucwords(str_replace('_', ' ', (string) $selectedChat->booking?->service_type)) : ($isCustomer ? 'Booking and service help' : 'Job and account help') }}</p>
            </div>
            @if (! $selectedChat)
                <span class="shrink-0 rounded-full border border-violet-100 bg-violet-50 px-2.5 py-1 text-xs font-bold uppercase tracking-[0.12em] text-violet-700 dark:border-violet-400/20 dark:bg-violet-400/10 dark:text-violet-200">Assistant</span>
            @endif
        </div>

        <div class="min-h-0 flex-1 space-y-3 overflow-y-auto bg-gradient-to-b from-slate-50/80 via-white to-slate-50/60 p-4 sm:p-5 dark:from-slate-950/70 dark:via-slate-900 dark:to-slate-950/50" aria-live="polite">
            @if ($selectedChat === null && $messages->isEmpty())
                <div class="mx-auto mt-5 max-w-sm rounded-3xl border border-indigo-100 bg-white p-5 text-center shadow-[0_12px_35px_-24px_rgba(79,70,229,0.45)] dark:border-white/10 dark:bg-white/5">
                    <span class="mx-auto flex size-11 items-center justify-center rounded-2xl bg-gradient-to-br from-violet-500 to-indigo-700 text-white shadow-lg shadow-indigo-500/20"><flux:icon name="sparkles" class="size-5" /></span>
                    <p class="mt-3 text-sm font-semibold text-slate-900 dark:text-white">How can I help today?</p>
                    <p class="mt-1 text-xs leading-relaxed text-slate-500 dark:text-slate-400">{{ $isCustomer ? 'Hi! Ask me how to book, follow a technician, review a quotation, or join a walk-in queue.' : 'Hi! Ask me how to accept requests, update jobs, prepare quotations, or manage your walk-in queue.' }}</p>
                </div>
            @endif
            @forelse ($messages as $message)
                @php
                    $fromMe = $selectedChat ? (int) $message->sender_id === (int) auth()->id() : $message->role === 'user';
                @endphp
                <div wire:key="message-{{ $selectedChat ? 'booking' : 'assistant' }}-{{ $message->id }}" class="flex {{ $fromMe ? 'justify-end' : 'justify-start' }}">
                    <div class="max-w-[88%] rounded-[1.25rem] px-4 py-3 text-sm leading-relaxed shadow-sm {{ $fromMe ? 'rounded-br-md bg-gradient-to-br from-indigo-600 to-indigo-700 text-white shadow-indigo-500/15' : 'rounded-bl-md border border-slate-200/80 bg-white text-slate-800 dark:border-white/10 dark:bg-white/10 dark:text-slate-100' }}">
                        <p class="whitespace-pre-wrap break-words">{{ $message->body }}</p>
                        <p class="mt-1.5 text-right text-xs font-medium {{ $fromMe ? 'text-indigo-100' : 'text-slate-400' }}">{{ $message->created_at?->format('g:i A') }}</p>
                    </div>
                </div>
            @empty
                @if ($selectedChat)
                    <p class="py-12 text-center text-sm text-slate-500 dark:text-slate-400">No messages yet.</p>
                @endif
            @endforelse
        </div>

        <form wire:submit="send" data-chat-composer class="shrink-0 border-t border-slate-200/70 bg-white {{ $compact ? 'p-3' : 'p-4 sm:p-5' }} dark:border-white/10 dark:bg-slate-900">
            <div class="flex items-end gap-2 rounded-2xl border border-slate-200 bg-slate-50/70 p-2 shadow-sm transition focus-within:border-indigo-300 focus-within:ring-4 focus-within:ring-indigo-500/10 dark:border-white/10 dark:bg-white/5 dark:focus-within:border-indigo-400/50">
                <div class="min-w-0 flex-1"><flux:textarea wire:model="draft" :rows="$compact ? 1 : 2" label="Message" placeholder="Write a message..." /><flux:error name="draft" /></div>
                <flux:button type="submit" variant="primary" icon="paper-airplane" wire:loading.attr="disabled" wire:target="send">Send</flux:button>
            </div>
        </form>
    </section>
</div>
