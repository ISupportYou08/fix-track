<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Symfony\Component\HttpFoundation\Response;

class EnsurePendingGoogleRegistration
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $registration = $request->session()->get('google_registration');
        $expiresAt = is_array($registration) ? Arr::get($registration, 'expires_at') : null;
        $googleId = is_array($registration) ? Arr::get($registration, 'google_id') : null;
        $email = is_array($registration) ? Arr::get($registration, 'email') : null;

        if (! is_numeric($expiresAt)
            || (int) $expiresAt <= now()->getTimestamp()
            || ! is_string($googleId)
            || blank($googleId)
            || ! is_string($email)
            || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $request->session()->forget('google_registration');

            return redirect()->route('login')->withErrors([
                'email' => __('Your Google registration session expired. Please continue with Google again.'),
            ]);
        }

        return $next($request);
    }
}
