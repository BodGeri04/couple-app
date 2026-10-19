<?php

namespace App\Http\Controllers;

use App\Models\PushSubscription;
use App\Services\PushService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PushController extends Controller
{
    public function subscribe(Request $request): JsonResponse
    {
        $data = $request->validate([
            'endpoint' => ['required', 'url:https', 'max:500'],
            'keys.p256dh' => ['required', 'string', 'max:255'],
            'keys.auth' => ['required', 'string', 'max:255'],
        ]);

        // If the same phone logs in as someone else, the subscription simply changes owner.
        PushSubscription::updateOrCreate(
            ['endpoint' => $data['endpoint']],
            [
                'user_id' => $request->user()->id,
                'public_key' => $data['keys']['p256dh'],
                'auth_token' => $data['keys']['auth'],
                'content_encoding' => 'aes128gcm',
                'user_agent' => Str::limit((string) $request->userAgent(), 250, ''),
                'last_used_at' => now(),
            ],
        );

        return response()->json(['ok' => true]);
    }

    public function unsubscribe(Request $request): JsonResponse
    {
        PushSubscription::where('endpoint', (string) $request->input('endpoint'))
            ->where('user_id', $request->user()->id)
            ->delete();

        return response()->json(['ok' => true]);
    }

    public function test(Request $request, PushService $push): JsonResponse
    {
        $sent = $push->sendToUser($request->user(), [
            'title' => 'Teszt értesítés 💗',
            'body' => 'Működik! Az értesítések be vannak kapcsolva.',
            'url' => '/dashboard',
            'tag' => 'test',
        ]);

        return response()->json(['sent' => $sent]);
    }
}