<?php

namespace App\Http\Middleware;

use App\Services\Audit;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Records every state-changing request made by a signed-in user.
 * Only the route, method and status are stored: never the request body
 * (it can hold passwords or photos). Admin routes log their own detailed entries.
 */
class LogActivity
{
    private const LABELS = [
        'calendar.store' => 'Naptáresemény létrehozva',
        'calendar.update' => 'Naptáresemény módosítva',
        'calendar.destroy' => 'Naptáresemény törölve',
        'shopping.store' => 'Bevásárlólista elem hozzáadva',
        'shopping.toggle' => 'Bevásárlólista elem kipipálva/visszaállítva',
        'shopping.destroy' => 'Bevásárlólista elem törölve',
        'shopping.clear' => 'Kipipált tételek törölve',
        'wishes.store' => 'Kívánság hozzáadva',
        'wishes.update' => 'Kívánság módosítva',
        'wishes.react' => 'Reakció kívánságra',
        'wishes.destroy' => 'Kívánság törölve',
        'snaps.store' => 'Pillanat elküldve',
        'snaps.destroy' => 'Pillanat törölve',
        'profile.update' => 'Profil módosítva',
        'profile.destroy' => 'Fiók törölve',
        'password.update' => 'Jelszó megváltoztatva',
        'push.subscribe' => 'Push értesítés bekapcsolva',
        'push.unsubscribe' => 'Push értesítés kikapcsolva',
        'push.test' => 'Teszt értesítés kérve',
    ];

    /** Too frequent to be useful in the log. */
    private const IGNORED = ['snaps.seen'];

    public function handle(Request $request, Closure $next): Response
    {
        return $next($request);
    }

    public function terminate(Request $request, Response $response): void
    {
        $route = $request->route()?->getName();

        if (
            $request->isMethodSafe()
            || ! $route
            || str_starts_with($route, 'admin.')
            || in_array($route, self::IGNORED, true)
            || ! ($user = $request->user())
        ) {
            return;
        }

        Audit::record(
            'activity.'.$route,
            self::LABELS[$route] ?? ($request->method().' '.$route),
            null,
            ['method' => $request->method(), 'path' => '/'.$request->path(), 'status' => $response->getStatusCode()],
            $user,
        );
    }
}
