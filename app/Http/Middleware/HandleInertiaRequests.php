<?php

namespace App\Http\Middleware;

use App\Models\DailySnap;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'is_admin' => $user->isAdmin(),
                ] : null,
                'partner' => $user?->partner()?->only(['id', 'name']),
            ],
            'push' => [
                'publicKey' => config('push.vapid.public'),
            ],
            'unseenSnaps' => fn () => $user
                ? DailySnap::where('recipient_id', $user->id)->whereNull('viewed_at')->count()
                : 0,
        ];
    }
}