<?php

namespace App\Providers;

use App\Services\Audit;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        Event::listen(Login::class, fn (Login $e) => Audit::record(
            'auth.login',
            'Bejelentkezés',
            null,
            $e->remember ? ['remember' => true] : [],
            $e->user,
        ));

        Event::listen(Logout::class, function (Logout $e) {
            if ($e->user) {
                Audit::record('auth.logout', 'Kijelentkezés', null, [], $e->user);
            }
        });

        // Only the e-mail is logged for a failed attempt, never the password.
        Event::listen(Failed::class, fn (Failed $e) => Audit::record(
            'auth.failed',
            'Sikertelen bejelentkezés: '.($e->credentials['email'] ?? '?'),
            null,
            ['email' => $e->credentials['email'] ?? null],
            $e->user,
        ));

        Event::listen(Lockout::class, fn (Lockout $e) => Audit::record(
            'auth.lockout',
            'Bejelentkezés zárolva túl sok próbálkozás miatt: '.$e->request->input('email'),
            null,
            ['email' => $e->request->input('email')],
        ));

        Event::listen(PasswordReset::class, fn (PasswordReset $e) => Audit::record(
            'auth.password_reset',
            'Jelszó visszaállítva e-mailben',
            null,
            [],
            $e->user,
        ));
    }
}
