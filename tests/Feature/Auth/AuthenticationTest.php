<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_login_redirects_to_intended_url_on_same_host_as_relative_path(): void
    {
        $user = User::factory()->create();
        URL::forceScheme('https');

        // Production shape: the app sees http behind the proxy, so the stored intended URL is http,
        // but the redirect must go to the forced https origin.
        $response = $this->withSession(['url.intended' => 'http://app.test/admin/users'])
            ->post('http://app.test/login', [
                'email' => $user->email,
                'password' => 'password',
            ]);

        $this->assertAuthenticated();
        $response->assertHeader('Location', 'https://app.test/admin/users');
    }

    public function test_login_ignores_intended_url_on_another_host(): void
    {
        $user = User::factory()->create();
        URL::forceScheme('https');

        $response = $this->withSession(['url.intended' => 'https://evil.example.com/steal'])
            ->post('http://app.test/login', [
                'email' => $user->email,
                'password' => 'password',
            ]);

        $this->assertAuthenticated();
        $response->assertHeader('Location', 'https://app.test/dashboard');
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
    }
}
