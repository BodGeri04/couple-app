<?php

namespace App\Providers;

use App\Services\Audit;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;
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
        if ($this->app->environment('production') || str_starts_with((string) config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }

        // The stored intended URL keeps the scheme the app saw (http behind the proxy), so it is
        // redirected by path, letting the forced https scheme apply. Other hosts fall back to the default.
        Redirector::macro('intendedPath', function (string $default, int $status = 302) {
            $path = $default;
            $intended = $this->session->pull('url.intended');

            if (is_string($intended) && $intended !== '') {
                $parts = parse_url($intended);

                if (! isset($parts['host'])) {
                    $path = $intended;
                } elseif (strcasecmp($parts['host'], request()->getHost()) === 0) {
                    $path = ($parts['path'] ?? '/').(isset($parts['query']) ? '?'.$parts['query'] : '');
                }
            }

            return $this->to($path, $status);
        });

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
