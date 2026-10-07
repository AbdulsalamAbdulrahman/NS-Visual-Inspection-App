<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Runs on every authenticated request:
 *  - signs out accounts suspended mid-session (they see the AU-05 state),
 *  - sends invited users to set their own password first (AU-03),
 *  - records last_active_at at most every five minutes.
 */
class EnsureAccountUsable
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            return $next($request);
        }

        if ($user->isSuspended()) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->with('suspended', true);
        }

        if ($user->must_change_password && ! $request->routeIs('password.first*', 'logout')) {
            return redirect()->route('password.first');
        }

        if ($user->last_active_at === null || $user->last_active_at->lt(now()->subMinutes(5))) {
            $user->forceFill(['last_active_at' => now()])->saveQuietly();
        }

        return $next($request);
    }
}
