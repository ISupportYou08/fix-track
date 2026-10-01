<?php

namespace App\Actions\Bookings;

use App\Models\Booking;
use App\Models\BookingChat;
use App\Models\User;

class OpenBookingChat
{
    public function execute(Booking $booking, User $technician): BookingChat
    {
        abort_unless((int) $booking->assigned_technician_id === (int) $technician->id, 403);

        $chat = BookingChat::query()->firstOrCreate(['booking_id' => $booking->id]);

        if ($chat->wasRecentlyCreated) {
            $serviceName = $booking->service?->name ?: str_replace('_', ' ', $booking->service_type);

            $chat->messages()->create([
                'sender_id' => $technician->id,
                'kind' => 'acceptance',
                'body' => "Hi {$booking->customer_name}, I've accepted your {$serviceName} booking. I'll keep you updated here.",
            ]);
        }

        return $chat;
    }
}
