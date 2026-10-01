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
            if (WalkInEntry::query()->where('user_id', $customer->id)->whereIn('status', WalkInEntry::ACTIVE_STATUSES)->lockForUpdate()->exists()) {
                throw ValidationException::withMessages([
                    'walkInQueue' => 'You already have an active Walk-In ticket.',
                ]);
            }

            if (empty($attributes['technician_id']) && filled($attributes['service_type'] ?? null)) {
                $verifications = TechnicianVerification::query()
                    ->where('status', 'approved')
                    ->with('technician')
                    ->get()
                    ->filter(fn (TechnicianVerification $verification): bool => $verification->technician?->hasActiveAccount()
                        && $verification->supportsService((string) $attributes['service_type']));
                $verification = $verifications->first(fn (TechnicianVerification $candidate): bool => WalkInEntry::query()
                    ->where('technician_id', $candidate->user_id)
                    ->whereIn('status', WalkInEntry::ACTIVE_STATUSES)
                    ->count() < WalkInEntry::MAX_ACTIVE);

                if ($verifications->isNotEmpty() && $verification === null) {
                    throw ValidationException::withMessages(['walkInQueue' => 'All matching walk-in shops are currently full. Please try again later.']);
                }

                $attributes['technician_id'] = $verification?->user_id;
            }

            $technicianId = $attributes['technician_id'] ?? null;
            if ($technicianId !== null) {
                $technician = User::query()->lockForUpdate()->findOrFail($technicianId);
                abort_unless($technician->isTechnician() && $technician->hasActiveAccount(), 422, 'This walk-in shop is unavailable.');
            }

            $activeShopCount = WalkInEntry::query()
                ->where('technician_id', $technicianId)
                ->whereIn('status', WalkInEntry::ACTIVE_STATUSES)
                ->lockForUpdate()
                ->count();

            if ($activeShopCount >= WalkInEntry::MAX_ACTIVE) {
                throw ValidationException::withMessages([
                    'walkInQueue' => 'Walk-In Queue is currently full for this shop. Maximum capacity is 3 customers. Please wait until a slot becomes available.',
                ]);
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
