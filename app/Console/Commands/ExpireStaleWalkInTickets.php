<?php

namespace App\Console\Commands;

use App\Models\WalkInEntry;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

#[Signature('walk-ins:expire-stale')]
#[Description('Mark unfinished Walk-In tickets from previous Manila calendar days as no-show.')]
class ExpireStaleWalkInTickets extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $cutoff = Carbon::now('Asia/Manila')->startOfDay()->utc();
        $expiredCount = 0;

        DB::transaction(function () use ($cutoff, &$expiredCount): void {
            WalkInEntry::query()
                ->whereIn('status', WalkInEntry::ACTIVE_STATUSES)
                ->where('checked_in_at', '<', $cutoff)
                ->lockForUpdate()
                ->each(function (WalkInEntry $entry) use (&$expiredCount): void {
                    $entry->transitionTo('no_show', reason: 'Walk-In ticket expired at the end of the service day.', metadata: ['source' => 'scheduler']);
                    $expiredCount++;
                });

            WalkInEntry::query()
                ->whereIn('status', WalkInEntry::ACTIVE_STATUSES)
                ->whereNotNull('user_id')
                ->orderBy('checked_in_at')
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->groupBy('user_id')
                ->each(function (Collection $entries) use (&$expiredCount): void {
                    $entries->skip(1)->each(function (WalkInEntry $entry) use (&$expiredCount): void {
                        $entry->transitionTo('cancelled', reason: 'Duplicate active Walk-In ticket closed by the system.', metadata: ['source' => 'scheduler']);
                        $expiredCount++;
                    });
                });

            WalkInEntry::query()
                ->whereIn('status', WalkInEntry::ACTIVE_STATUSES)
                ->orderBy('checked_in_at')
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->skip(WalkInEntry::MAX_ACTIVE)
                ->each(function (WalkInEntry $entry) use (&$expiredCount): void {
                    $entry->transitionTo('cancelled', reason: 'Walk-In queue capacity normalized by the system.', metadata: ['source' => 'scheduler']);
                    $expiredCount++;
                });
        });

        $this->info("Expired {$expiredCount} stale Walk-In ticket(s).");

        return self::SUCCESS;
    }
}
