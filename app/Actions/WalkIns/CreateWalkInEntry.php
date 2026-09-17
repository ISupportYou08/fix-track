<?php

namespace App\Actions\WalkIns;

use App\Models\TechnicianVerification;
use App\Models\User;
use App\Models\WalkInEntry;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CreateWalkInEntry
{
    /** @param array<string, mixed> $attributes */
    public function execute(User $customer, array $attributes): WalkInEntry
    {
        return DB::transaction(function () use ($customer, $attributes): WalkInEntry {
            $activeEntries = WalkInEntry::query()
                ->whereIn('status', WalkInEntry::ACTIVE_STATUSES)
                ->orderBy('id')
                ->lockForUpdate()
                ->get(['id', 'user_id']);

            if ($activeEntries->count() >= WalkInEntry::MAX_ACTIVE) {
                throw ValidationException::withMessages([
                    'walkInQueue' => 'Walk-In Queue is currently full. Maximum capacity is 3 customers. Please wait until a slot becomes available.',
                ]);
            }

            if ($activeEntries->contains('user_id', $customer->id)) {
                throw ValidationException::withMessages([
                    'walkInQueue' => 'You already have an active Walk-In ticket.',
                ]);
            }

            if (empty($attributes['technician_id']) && filled($attributes['service_type'] ?? null)) {
                $verification = TechnicianVerification::query()
                    ->where('status', 'approved')
                    ->with('technician')
                    ->get()
                    ->first(fn (TechnicianVerification $verification): bool => $verification->technician?->hasActiveAccount()
                        && $verification->supportsService((string) $attributes['service_type']));

                $attributes['technician_id'] = $verification?->user_id;
            }

            $entry = WalkInEntry::query()->create([
                ...$attributes,
                'user_id' => $customer->id,
                'reference' => $this->uniqueReference(),
                'queue_number' => 'PENDING-'.Str::uuid(),
                'priority' => 'standard',
                'status' => 'waiting',
                'payment_method' => 'cash',
                'counter_id' => null,
                'checked_in_at' => now(),
            ]);

            $entry->forceFill([
                'queue_number' => 'W'.str_pad((string) $entry->id, 3, '0', STR_PAD_LEFT),
            ])->save();
            $entry->recordInitialStatus($customer, ['source' => 'customer']);

            return $entry->refresh();
        }, 3);
    }

    private function uniqueReference(): string
    {
        do {
            $reference = 'WI-'.Str::upper(Str::random(8));
        } while (WalkInEntry::query()->where('reference', $reference)->exists());

        return $reference;
    }
}
