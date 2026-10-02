<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Throwable;

class DatabaseMigrationController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $cronSecret = (string) config('services.vercel.cron_secret');
        $authorization = (string) $request->header('Authorization');

        abort_unless(
            $cronSecret !== '' && hash_equals('Bearer '.$cronSecret, $authorization),
            401,
        );

        try {
            $exitCode = Artisan::call('migrate', [
                '--force' => true,
                '--no-interaction' => true,
            ]);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'ok' => false,
                'message' => 'Database migrations failed.',
            ], 500);
        }

        return response()->json([
            'ok' => $exitCode === 0,
            'message' => $exitCode === 0
                ? 'Database migrations completed.'
                : 'Database migrations failed.',
        ], $exitCode === 0 ? 200 : 500);
    }
}
