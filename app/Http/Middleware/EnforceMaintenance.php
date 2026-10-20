<?php

namespace App\Http\Middleware;

use App\Services\Settings;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** While maintenance mode is on, signed-in non-admins are sent to the maintenance page. */
class EnforceMaintenance
{
    private const ALWAYS_ALLOWED = ['maintenance', 'logout'];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (
            $user
            && ! $user->isAdmin()
            && Settings::bool('maintenance')
            && ! in_array($request->route()?->getName(), self::ALWAYS_ALLOWED, true)
        ) {
            return redirect()->route('maintenance');
        }

        return $next($request);
    }
}
