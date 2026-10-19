<?php

namespace App\Services;

use App\Models\PushSubscription;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;
use Throwable;

class PushService
{
    /**
     * Send a notification to every device the user has subscribed.
     *
     * @param  array{title: string, body?: string, url?: string, tag?: string}  $payload
     * @return int number of devices that accepted the message
     */
    public function sendToUser(User $user, array $payload): int
    {
        if (! config('push.vapid.private') || ! config('push.vapid.public')) {
            return 0;
        }

        $subscriptions = PushSubscription::where('user_id', $user->id)->get();

        if ($subscriptions->isEmpty()) {
            return 0;
        }

        try {
            $webPush = new WebPush(
                [
                    'VAPID' => [
                        'subject' => config('push.vapid.subject'),
                        'publicKey' => config('push.vapid.public'),
                        'privateKey' => config('push.vapid.private'),
                    ],
                ],
                ['TTL' => 86400, 'urgency' => 'high'],
                new \GuzzleHttp\Client(['timeout' => 10]),
            );

            $json = json_encode($payload, JSON_UNESCAPED_UNICODE);

            foreach ($subscriptions as $subscription) {
                $webPush->queueNotification(
                    Subscription::create([
                        'endpoint' => $subscription->endpoint,
                        'publicKey' => $subscription->public_key,
                        'authToken' => $subscription->auth_token,
                        'contentEncoding' => $subscription->content_encoding ?: 'aes128gcm',
                    ]),
                    $json,
                );
            }

            $sent = 0;

            foreach ($webPush->flush() as $report) {
                if ($report->isSuccess()) {
                    $sent++;
                } elseif ($report->isSubscriptionExpired()) {
                    // The phone removed the subscription: forget it.
                    PushSubscription::where('endpoint', $report->getEndpoint())->delete();
                } else {
                    Log::warning('Web push failed', [
                        'endpoint' => $report->getEndpoint(),
                        'reason' => $report->getReason(),
                    ]);
                }
            }

            return $sent;
        } catch (Throwable $e) {
            Log::warning('Web push error: ' . $e->getMessage());

            return 0;
        }
    }
}
