<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingChatMessage extends Model
{
    protected $fillable = ['booking_chat_id', 'sender_id', 'kind', 'body', 'read_at'];

    protected function casts(): array
    {
        return ['read_at' => 'datetime'];
    }

    /** @return BelongsTo<BookingChat, $this> */
    public function chat(): BelongsTo
    {
        return $this->belongsTo(BookingChat::class, 'booking_chat_id');
    }

    /** @return BelongsTo<User, $this> */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }
}
