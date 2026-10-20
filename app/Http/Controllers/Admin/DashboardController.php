<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\DailySnap;
use App\Models\PushSubscription;
use App\Models\User;
use App\Services\Settings;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        $since = now()->subDay();

        $devices = PushSubscription::query()
            ->selectRaw('user_id, count(*) as total')
            ->groupBy('user_id')
            ->pluck('total', 'user_id');

        return Inertia::render('Admin/Dashboard', [
            'stats' => [
                'users' => User::count(),
                'snaps' => DailySnap::count(),
                'hidden_snaps' => DailySnap::whereNotNull('hidden_at')->count(),
                'devices' => PushSubscription::count(),
                'events_24h' => AuditLog::where('created_at', '>=', $since)->count(),
                'failed_logins_24h' => AuditLog::where('event', 'auth.failed')->where('created_at', '>=', $since)->count(),
            ],
            'settings' => [
                'system' => Settings::inSection('system'),
                'features' => Settings::inSection('features'),
            ],
            'pushConfigured' => (bool) (config('push.vapid.public') && config('push.vapid.private')),
            'users' => User::orderBy('id')->get(['id', 'name', 'email'])->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'devices' => (int) ($devices[$user->id] ?? 0),
            ]),
            'recent' => AuditLog::latest('id')->limit(8)->get()->map(fn (AuditLog $log) => [
                'id' => $log->id,
                'category' => $log->category,
                'description' => $log->description,
                'actor' => $log->actor_name,
                'created_at' => $log->created_at->toIso8601String(),
            ]),
        ]);
    }
}
