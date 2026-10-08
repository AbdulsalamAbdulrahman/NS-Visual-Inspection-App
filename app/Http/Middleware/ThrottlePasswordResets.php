<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Fortify doesn't throttle "forgot password" or "reset password". Limit both
 * so nobody can flood a contractor's inbox or guess reset tokens: 5 a minute
 * per email and IP, 20 an hour per IP.
 */
class ThrottlePasswordResets
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->isMethod('POST') || ! $request->routeIs('password.email', 'password.update')) {
            return $next($request);
        }

        $ip = (string) $request->ip();
        $perEmail = 'password-reset:'.Str::lower((string) $request->input('email')).'|'.$ip;
        $perIp = 'password-reset-ip:'.$ip;

        foreach ([[$perEmail, 5, 60], [$perIp, 20, 3600]] as [$key, $max, $decay]) {
            if (RateLimiter::tooManyAttempts($key, $max)) {
                throw ValidationException::withMessages([
                    'email' => 'Too many attempts. Try again in '.max(1, (int) ceil(RateLimiter::availableIn($key) / 60)).' minute(s).',
                ]);
            }
        }

        RateLimiter::hit($perEmail, 60);
        RateLimiter::hit($perIp, 3600);

        return $next($request);
    }
}
