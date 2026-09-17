<?php

namespace App\Models;

use Database\Factories\WalkInEntryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WalkInEntry extends Model
{
    /** @use HasFactory<WalkInEntryFactory> */
    use HasFactory;

    public const MAX_ACTIVE = 3;

    public const ACTIVE_STATUSES = ['waiting', 'called', 'serving', 'on_hold'];

    public const STATUSES = ['waiting', 'called', 'serving', 'on_hold', 'completed', 'no_show', 'cancelled'];

    private const TRANSITIONS = [
        'waiting' => ['called', 'serving', 'on_hold', 'no_show', 'cancelled'],
        'called' => ['serving', 'on_hold', 'no_show', 'cancelled'],
        'serving' => ['on_hold', 'completed', 'cancelled'],
        'on_hold' => ['serving', 'cancelled'],
        'completed' => [],
        'no_show' => [],
        'cancelled' => [],
    ];

    protected $fillable = [
        'user_id',
        'technician_id',
        'reference',
        'queue_number',
        'customer_name',
        'customer_email',
        'customer_phone',
        'service_type',
        'priority',
        'status',
        'payment_method',
        'counter_id',
        'notes',
        'checked_in_at',
        'called_at',
        'service_started_at',
        'completed_at',
        'cancelled_at',
        'cancelled_by',
        'cancellation_reason',
        'customer_latitude',
        'customer_longitude',
        'location_sharing_enabled',
        'location_updated_at',
    ];

    protected function casts(): array
    {
        return [
            'checked_in_at' => 'datetime',
            'called_at' => 'datetime',
            'service_started_at' => 'datetime',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'customer_latitude' => 'decimal:7',
            'customer_longitude' => 'decimal:7',
            'location_sharing_enabled' => 'boolean',
            'location_updated_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** @return BelongsTo<User, $this> */
    public function technician(): BelongsTo
    {
        return $this->belongsTo(User::class, 'technician_id');
    }

    /** @return BelongsTo<ServiceCounter, $this> */
    public function counter(): BelongsTo
    {
        return $this->belongsTo(ServiceCounter::class, 'counter_id');
    }

    /** @return BelongsTo<User, $this> */
    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    /** @return HasMany<WalkInStatusHistory, $this> */
    public function statusHistory(): HasMany
    {
        return $this->hasMany(WalkInStatusHistory::class)->latest('created_at');
    }

    public function isActive(): bool
    {
        return in_array($this->status, self::ACTIVE_STATUSES, true);
    }

    public function queuePosition(): ?int
    {
        if (! $this->isActive()) {
            return null;
        }

        return self::query()
            ->whereIn('status', self::ACTIVE_STATUSES)
            ->where(function ($query): void {
                $query->where('checked_in_at', '<', $this->checked_in_at)
                    ->orWhere(function ($query): void {
                        $query->where('checked_in_at', $this->checked_in_at)
                            ->where('id', '<=', $this->id);
                    });
            })
            ->count();
    }

    /** @param array<string, mixed> $metadata */
    public function recordInitialStatus(?User $actor = null, array $metadata = []): WalkInStatusHistory
    {
        return $this->statusHistory()->create([
            'from_status' => null,
            'to_status' => $this->status,
            'actor_id' => $actor?->id,
            'reason' => 'Walk-in ticket created.',
            'metadata' => $metadata,
            'created_at' => now(),
        ]);
    }

    /** @param array<string, mixed> $metadata */
    public function transitionTo(string $status, ?User $actor = null, ?string $reason = null, array $metadata = []): bool
    {
        abort_unless(in_array($status, self::STATUSES, true), 422, 'The walk-in status is invalid.');

        if ($this->status === $status) {
            return false;
        }

        abort_unless(
            in_array($status, self::TRANSITIONS[(string) $this->status] ?? [], true),
            422,
            "A walk-in ticket cannot move from {$this->status} to {$status}.",
        );

        $fromStatus = $this->status;
        $data = ['status' => $status];

        if ($status === 'called') {
            $data['called_at'] = now();
        }

        if ($status === 'serving') {
            $data['service_started_at'] = now();
        }

        if ($status === 'completed') {
            $data['completed_at'] = now();
        }

        if ($status === 'cancelled') {
            $data['cancelled_at'] = now();
            $data['cancelled_by'] = $actor?->id;
            $data['cancellation_reason'] = $reason;
        }

        if (! in_array($status, self::ACTIVE_STATUSES, true)) {
            $data += [
                'location_sharing_enabled' => false,
                'customer_latitude' => null,
                'customer_longitude' => null,
                'location_updated_at' => null,
            ];
        }

        $this->forceFill($data)->save();
        $this->statusHistory()->create([
            'from_status' => $fromStatus,
            'to_status' => $status,
            'actor_id' => $actor?->id,
            'reason' => $reason,
            'metadata' => $metadata,
            'created_at' => now(),
        ]);

        return true;
    }
}
