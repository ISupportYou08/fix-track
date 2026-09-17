<?php

use App\Models\WalkInEntry;

test('the Vercel cron endpoint requires its configured bearer secret', function () {
    config()->set('services.vercel.cron_secret', 'test-cron-secret');

    $this->getJson(route('internal.cron.expire-walk-ins'))
        ->assertUnauthorized();

    $this->withHeader('Authorization', 'Bearer incorrect-secret')
        ->getJson(route('internal.cron.expire-walk-ins'))
        ->assertUnauthorized();
});

test('the authorized Vercel cron expires stale Walk-In tickets', function () {
    config()->set('services.vercel.cron_secret', 'test-cron-secret');
    $ticket = WalkInEntry::factory()->create([
        'status' => 'waiting',
        'checked_in_at' => now()->subDays(2),
    ]);

    $this->withHeader('Authorization', 'Bearer test-cron-secret')
        ->getJson(route('internal.cron.expire-walk-ins'))
        ->assertOk()
        ->assertJson([
            'ok' => true,
            'message' => 'Walk-In queue maintenance completed.',
        ]);

    expect($ticket->refresh()->status)->toBe('no_show');
});

test('the Vercel deployment configuration uses PHP 8.4 and routes through Laravel', function () {
    $configuration = json_decode(
        file_get_contents(base_path('vercel.json')),
        true,
        flags: JSON_THROW_ON_ERROR,
    );

    expect(base_path('api/index.php'))->toBeFile()
        ->and(public_path('build/manifest.json'))->toBeFile()
        ->and(base_path('prepare-vercel-assets.mjs'))->toBeFile()
        ->and($configuration['buildCommand'])->toBe('node prepare-vercel-assets.mjs')
        ->and($configuration['outputDirectory'])->toBe('dist')
        ->and($configuration['functions']['api/index.php']['runtime'])->toBe('vercel-php@0.8.0')
        ->and(collect($configuration['routes'])->last()['dest'])->toBe('/api/index.php')
        ->and($configuration['crons'][0]['path'])->toBe('/internal/cron/expire-walk-ins');
});
