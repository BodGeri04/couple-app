<?php

namespace Tests\Feature\Admin;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    private const ROUTES = [
        'admin.dashboard',
        'admin.users.index',
        'admin.cms.index',
        'admin.media.index',
        'admin.logs.index',
    ];

    public function test_guests_are_redirected_to_login(): void
    {
        foreach (self::ROUTES as $name) {
            $this->get(route($name))->assertRedirect(route('login'));
        }
    }

    public function test_partners_get_403_on_every_admin_page(): void
    {
        $partner = User::factory()->create();

        foreach (self::ROUTES as $name) {
            $this->actingAs($partner)->get(route($name))->assertForbidden();
        }
    }

    public function test_partners_cannot_use_admin_write_endpoints(): void
    {
        $partner = User::factory()->create();
        $other = User::factory()->create();

        $this->actingAs($partner)->patch(route('admin.settings.update'), ['settings' => ['maintenance' => true]])->assertForbidden();
        $this->actingAs($partner)->post(route('admin.notifications.test'), ['title' => 'x'])->assertForbidden();
        $this->actingAs($partner)->patch(route('admin.users.update', $other), ['name' => 'Hacked', 'email' => 'h@example.com'])->assertForbidden();
        $this->actingAs($partner)->put(route('admin.cms.menu'), ['items' => []])->assertForbidden();

        $this->assertNotSame('Hacked', $other->fresh()->name);
    }

    public function test_denied_access_is_audited(): void
    {
        $partner = User::factory()->create();

        $this->actingAs($partner)->get(route('admin.dashboard'))->assertForbidden();

        $this->assertDatabaseHas('audit_logs', ['event' => 'access.denied', 'user_id' => $partner->id]);
    }

    public function test_admin_can_open_every_page(): void
    {
        $admin = User::factory()->admin()->create();

        foreach (self::ROUTES as $name) {
            $this->actingAs($admin)
                ->withSession(['auth.password_confirmed_at' => time()])
                ->get(route($name))
                ->assertOk();
        }
    }

    public function test_user_list_asks_for_password_confirmation_first(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get(route('admin.users.index'))->assertRedirect(route('password.confirm'));
    }

    public function test_admin_flag_is_shared_with_the_frontend_only_for_admins(): void
    {
        $partner = User::factory()->create();
        $admin = User::factory()->admin()->create();

        $this->actingAs($partner)->get(route('dashboard'))
            ->assertInertia(fn ($page) => $page->where('auth.user.is_admin', false));

        $this->actingAs($admin)->get(route('dashboard'))
            ->assertInertia(fn ($page) => $page->where('auth.user.is_admin', true));
    }

    public function test_role_cannot_be_escalated_through_the_profile_form(): void
    {
        $partner = User::factory()->create();

        $this->actingAs($partner)->patch('/profile', [
            'name' => 'Test', 'email' => $partner->email, 'role' => 'admin',
        ]);

        $this->assertSame('partner', $partner->fresh()->role);
        $this->assertSame(0, AuditLog::where('event', 'like', 'admin.%')->count());
    }
}
