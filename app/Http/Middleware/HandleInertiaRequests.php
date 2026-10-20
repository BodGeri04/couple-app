<?php

namespace App\Http\Middleware;

use App\Models\DailySnap;
use App\Models\MenuItem;
use App\Services\Settings;
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
            'site' => fn () => [
                'name' => Settings::get('app_name') ?: config('app.name'),
                'primary' => Settings::primaryRgb(),
                'features' => [
                    'calendar' => Settings::bool('feature_calendar'),
                    'snaps' => Settings::bool('feature_snaps'),
                    'shopping' => Settings::bool('feature_shopping'),
                    'wishes' => Settings::bool('feature_wishes'),
                ],
                'ui' => [
                    'welcome_text' => Settings::get('welcome_text'),
                    'install_card' => Settings::bool('show_install_card'),
                    'notification_card' => Settings::bool('show_notification_card'),
                    'countdown' => Settings::bool('show_countdown'),
                    'tiles' => Settings::bool('show_tiles'),
                    'burst_button' => Settings::bool('show_burst_button'),
                ],
                'menu' => MenuItem::navigation(),
            ],
            'flash' => fn () => session('flash'),
            'unseenSnaps' => fn () => $user
                ? DailySnap::visible()->where('recipient_id', $user->id)->whereNull('viewed_at')->count()
                : 0,
        ];
    }
}