<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PushSubscription;
use App\Models\User;
use App\Services\Audit;
use App\Services\PushService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function test(Request $request, PushService $push): RedirectResponse
    {
        $data = $request->validate([
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'title' => ['required', 'string', 'max:80'],
            'body' => ['nullable', 'string', 'max:200'],
            'url' => ['nullable', 'string', 'max:200', 'regex:/^\/(?!\/)\S*$/'],
        ], [
            'url.regex' => 'A cél csak az appon belüli útvonal lehet, pl. /dashboard.',
        ], ['title' => 'cím', 'body' => 'szöveg']);

        $targets = isset($data['user_id']) ? User::whereKey($data['user_id'])->get() : User::orderBy('id')->get();

        $report = [];

        foreach ($targets as $user) {
            $devices = PushSubscription::where('user_id', $user->id)->count();

            $sent = $devices
                ? $push->sendToUser($user, [
                    'title' => $data['title'],
                    'body' => $data['body'] ?? '',
                    'url' => $data['url'] ?? '/dashboard',
                    'tag' => 'admin-test',
                ], force: true)
                : 0;

            $report[] = ['user' => $user->name, 'devices' => $devices, 'sent' => $sent];
        }

        Audit::record('admin.notification.test', 'Teszt értesítés küldése: '.$targets->pluck('name')->implode(', '), null, [
            'title' => $data['title'],
            'result' => $report,
        ]);

        $summary = collect($report)->map(fn (array $row) => $row['devices']
            ? "{$row['user']}: {$row['sent']}/{$row['devices']} eszköz"
            : "{$row['user']}: nincs feliratkozott eszköz")->implode(' • ');

        $anySent = collect($report)->sum('sent') > 0;

        return back()->with('flash', [
            'type' => $anySent ? 'success' : 'warning',
            'message' => ($anySent ? 'Elküldve — ' : 'Nem sikerült kézbesíteni — ').$summary,
        ]);
    }
}
