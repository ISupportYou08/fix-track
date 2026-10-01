<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class BookingChat extends Model
{
    protected $fillable = ['booking_id'];

    /** @return BelongsTo<Booking, $this> */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    /** @return HasMany<BookingChatMessage, $this> */
    public function messages(): HasMany
    {
        return $this->hasMany(BookingChatMessage::class);
    }

    /** @return HasOne<BookingChatMessage, $this> */
    public function latestMessage(): HasOne
    {
        return $this->hasOne(BookingChatMessage::class)->latestOfMany();
    }
}
