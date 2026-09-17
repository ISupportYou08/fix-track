<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class VercelCronController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $cronSecret = (string) config('services.vercel.cron_secret');
        $authorization = (string) $request->header('Authorization');

        abort_unless(
            $cronSecret !== '' && hash_equals('Bearer '.$cronSecret, $authorization),
            401,
        );

        $exitCode = Artisan::call('walk-ins:expire-stale');

        return response()->json([
            'ok' => $exitCode === 0,
            'message' => $exitCode === 0
                ? 'Walk-In queue maintenance completed.'
                : 'Walk-In queue maintenance failed.',
        ], $exitCode === 0 ? 200 : 500);
    }
}
