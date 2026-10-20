<?php

namespace App\Http\Middleware;

use App\Services\Audit;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user || ! in_array($user->role, $roles, true)) {
            if ($user) {
                Audit::record('access.denied', 'Jogosulatlan hozzáférési kísérlet: '.$request->method().' /'.$request->path(), null, [
                    'required_role' => $roles,
                    'route' => $request->route()?->getName(),
                ]);
            }

            abort(403);
        }

        return $next($request);
    }
}