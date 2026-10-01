<?php

use App\Actions\ExpireTechnicianSuspension;
use App\Models\PendingTechnicianRegistration;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Storage;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('schema:check', function (): int {
    $migrator = app('migrator');
    $repository = $migrator->getRepository();

    if (! $repository->repositoryExists()) {
        $this->error('Migration repository is missing.');

        return 1;
    }

    $migrationNames = array_keys($migrator->getMigrationFiles(database_path('migrations')));
    $ran = $repository->getRan();
    $pending = array_values(array_diff($migrationNames, $ran));
    $missingFiles = array_values(array_diff($ran, $migrationNames));

    if ($pending === [] && $missingFiles === []) {
        $this->info('Database schema is up to date.');

        return 0;
    }

    if ($pending !== []) {
        $this->error('Pending migrations detected:');
        foreach ($pending as $migration) {
            $this->line(' - '.$migration);
        }
    }

    if ($missingFiles !== []) {
        $this->error('Applied migrations have no local file:');
        foreach ($missingFiles as $migration) {
            $this->line(' - '.$migration);
        }
    }

    return 1;
})->purpose('Fail when the database is behind the migration files');

Artisan::command('technicians:expire-suspensions', function (): void {
    $count = app(ExpireTechnicianSuspension::class)->restoreDue();
    $this->info("Restored {$count} expired technician suspension(s).");
})->purpose('Restore technician accounts whose suspension period has ended');

Schedule::command('technicians:expire-suspensions')->everyMinute()->withoutOverlapping();

Schedule::command('walk-ins:expire-stale')->hourly();

Schedule::call(function (): void {
    PendingTechnicianRegistration::query()
        ->where('registration_expires_at', '<=', now())
        ->chunkById(100, function ($registrations): void {
            foreach ($registrations as $registration) {
                Storage::disk('local')->delete(array_values($registration->payload['paths'] ?? []));
                $registration->delete();
            }
        });
})->hourly();
