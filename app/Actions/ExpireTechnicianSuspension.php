<?php

namespace App\Actions;

use App\Models\AuditLog;
use App\Models\User;
use App\Notifications\TechnicianAccountStatusNotification;
use Illuminate\Support\Facades\DB;
use Throwable;

class ExpireTechnicianSuspension
{
    public function restoreIfExpired(User $technician): bool
    {
        $restored = DB::transaction(function () use ($technician): ?User {
            $current = User::query()->lockForUpdate()->find($technician->id);

            if (! $current instanceof User
                || ! $current->isTechnician()
                || $current->account_status !== 'suspended'
                || $current->suspended_until === null
                || $current->suspended_until->isFuture()) {
                return null;
            }

            $expiredAt = $current->suspended_until;
            $current->update([
                'account_status' => 'active',
                'suspended_until' => null,
                'availability_status' => 'offline',
            ]);

            AuditLog::query()->create([
                'user_id' => null,
                'action' => 'technician.account_status_updated',
                'target_type' => 'user',
                'target_id' => $current->id,
                'details' => [
                    'previous_status' => 'suspended',
                    'account_status' => 'active',
                    'reason' => 'Suspension period ended automatically.',
                    'expired_at' => $expiredAt->toIso8601String(),
                    'automatic' => true,
                ],
                'created_at' => now(),
            ]);

            return $current;
        });

        if ($restored === null) {
            return false;
        }

        $technician->refresh();

        try {
            $restored->notify(new TechnicianAccountStatusNotification('active', 'Your suspension period has ended.'));
        } catch (Throwable $exception) {
            report($exception);
        }

        return true;
    }

    public function restoreDue(): int
    {
        $count = 0;

        User::query()
            ->where('role', 'technician')
            ->where('account_status', 'suspended')
            ->whereNotNull('suspended_until')
            ->where('suspended_until', '<=', now())
            ->chunkById(100, function ($technicians) use (&$count): void {
                foreach ($technicians as $technician) {
                    if ($this->restoreIfExpired($technician)) {
                        $count++;
                    }
                }
            });

        return $count;
    }
}
