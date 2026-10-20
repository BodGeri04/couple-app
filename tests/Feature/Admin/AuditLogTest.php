<?php

namespace Tests\Feature\Admin;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_failed_login_and_logout_are_recorded(): void
    {
        $user = User::factory()->create(['password' => bcrypt('secret-pass-1')]);

        $this->post('/login', ['email' => $user->email, 'password' => 'wrong-password']);
        $this->post('/login', ['email' => $user->email, 'password' => 'secret-pass-1']);
        $this->post('/logout');

        $this->assertDatabaseHas('audit_logs', ['event' => 'auth.failed']);
        $this->assertDatabaseHas('audit_logs', ['event' => 'auth.login', 'user_id' => $user->id]);
        $this->assertDatabaseHas('audit_logs', ['event' => 'auth.logout', 'user_id' => $user->id]);

        $this->assertStringNotContainsString('wrong-password', json_encode(AuditLog::all()->toArray()));
        $this->assertStringNotContainsString('secret-pass-1', json_encode(AuditLog::all()->toArray()));
    }

    public function test_state_changing_user_requests_are_recorded_without_the_body(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('shopping.store'), ['name' => 'Tej-titok-123']);

        $log = AuditLog::where('event', 'activity.shopping.store')->firstOrFail();
        $this->assertSame($user->id, $log->user_id);
        $this->assertStringNotContainsString('Tej-titok-123', json_encode($log->toArray()));

        $this->actingAs($user)->get('/shopping');
        $this->assertSame(1, AuditLog::where('event', 'like', 'activity.%')->count());
    }

    public function test_admin_can_filter_the_log_and_partners_cannot_see_it(): void
    {
        $admin = User::factory()->admin()->create();
        $partner = User::factory()->create();

        $this->post('/login', ['email' => 'nobody@example.com', 'password' => 'x']); // as a guest
        $this->actingAs($partner)->post(route('shopping.store'), ['name' => 'Kenyér']);

        $this->actingAs($admin)->get(route('admin.logs.index', ['category' => 'activity']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Logs')
                ->where('logs.data', fn ($rows) => collect($rows)->every(fn ($r) => $r['category'] === 'activity') && count($rows) === 1));

        $this->actingAs($admin)->get(route('admin.logs.index', ['q' => 'nobody@example.com']))
            ->assertInertia(fn ($page) => $page->has('logs.data', 1));

        $this->actingAs($admin)->get(route('admin.logs.index', ['category' => 'bogus']))
            ->assertSessionHasErrors('category');

        $this->actingAs($partner)->get(route('admin.logs.index'))->assertForbidden();
    }

    public function test_the_log_is_read_only(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->delete('/admin/logs/1')->assertStatus(404);
        $this->actingAs($admin)->patch('/admin/logs/1')->assertStatus(404);
    }
}
