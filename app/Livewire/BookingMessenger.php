<?php

namespace App\Livewire;

use App\Actions\Messages\ReplyToAssistant;
use App\Models\AssistantMessage;
use App\Models\BookingChat;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;

class BookingMessenger extends Component
{
    #[Locked]
    public ?int $selectedChatId = null;

    #[Locked]
    public bool $compact = false;

    public string $draft = '';

    public bool $showContacts = false;

    public function mount(bool $compact = false): void
    {
        abort_unless(auth()->user() instanceof User && (auth()->user()->isCustomer() || auth()->user()->isTechnician()), 403);
        $this->compact = $compact;
    }

    public function selectAssistant(): void
    {
        abort_unless(auth()->user()?->isCustomer() || auth()->user()?->isTechnician(), 403);
        $this->selectedChatId = null;
        $this->draft = '';
        $this->showContacts = false;
    }

    public function toggleContacts(): void
    {
        abort_unless(auth()->user()?->isCustomer() || auth()->user()?->isTechnician(), 403);
        $this->showContacts = ! $this->showContacts;
    }

    public function selectChat(int $chatId): void
    {
        $chat = $this->accessibleChats()->findOrFail($chatId);
        $this->selectedChatId = $chat->id;
        $this->draft = '';
        $this->showContacts = false;
        $chat->messages()->whereNull('read_at')->where('sender_id', '!=', auth()->id())->update(['read_at' => now()]);
    }

    public function send(ReplyToAssistant $assistant): void
    {
        $validated = Validator::make(['draft' => trim($this->draft)], [
            'draft' => ['required', 'string', 'min:1', 'max:2000'],
        ])->validate();

        $key = 'booking-message:'.auth()->id();
        abort_if(RateLimiter::tooManyAttempts($key, 20), 429, 'Please wait before sending another message.');
        RateLimiter::hit($key, 60);

        if ($this->selectedChatId === null) {
            $user = auth()->user();
            abort_unless($user instanceof User && ($user->isCustomer() || $user->isTechnician()), 403);
            AssistantMessage::query()->create([
                'user_id' => $user->id,
                'role' => 'user',
                'body' => $validated['draft'],
            ]);

            AssistantMessage::query()->create([
                'user_id' => $user->id,
                'role' => 'assistant',
                'body' => $assistant->reply(
                    (string) $validated['draft'],
                    AssistantMessage::query()->where('user_id', $user->id)->latest('id')->limit(10)->get()->reverse()->values(),
                    $user,
                ),
            ]);
        } else {
            $chat = $this->accessibleChats()->findOrFail($this->selectedChatId);
            $chat->messages()->create([
                'sender_id' => auth()->id(),
                'kind' => 'message',
                'body' => $validated['draft'],
            ]);
        }

        $this->draft = '';
    }

    public function render(): View
    {
        $isCustomer = auth()->user()?->isCustomer() ?? false;
        $chats = $this->accessibleChats()
            ->with(['booking.customer:id,name', 'booking.technician:id,name', 'latestMessage'])
            ->withMax('messages', 'id')
            ->orderByDesc('messages_max_id')
            ->latest('created_at')
            ->limit(30)
            ->get();
        $selectedChat = $this->selectedChatId !== null
            ? $this->accessibleChats()->with(['booking.customer:id,name', 'booking.technician:id,name'])->findOrFail($this->selectedChatId)
            : null;

        return view('livewire.booking-messenger', [
            'isCustomer' => $isCustomer,
            'chats' => $chats,
            'selectedChat' => $selectedChat,
            'messages' => $selectedChat !== null
                ? $selectedChat->messages()->with('sender:id,name')->latest('id')->limit(100)->get()->reverse()
                : AssistantMessage::query()->where('user_id', auth()->id())->latest('id')->limit(100)->get()->reverse(),
        ]);
    }

    /** @return Builder<BookingChat> */
    private function accessibleChats(): Builder
    {
        $user = auth()->user();
        abort_unless($user instanceof User, 403);

        return BookingChat::query()->whereHas('booking', function (Builder $query) use ($user): void {
            $query->where($user->isCustomer() ? 'user_id' : 'assigned_technician_id', $user->id);
        });
    }
}
