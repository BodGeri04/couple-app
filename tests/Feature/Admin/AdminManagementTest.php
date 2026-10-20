<?php

namespace Tests\Feature\Admin;

use App\Models\AuditLog;
use App\Models\User;
use App\Services\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdminManagementTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    private function confirmed(): array
    {
        return ['auth.password_confirmed_at' => time()];
    }

    // ---- settings / toggles -------------------------------------------------

    public function test_admin_can_update_settings_and_it_is_audited(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->patch(route('admin.settings.update'), [
            'settings' => ['feature_wishes' => false, 'app_name' => 'Mi', 'primary_color' => '#336699'],
        ])->assertSessionHasNoErrors();

        $this->assertFalse(Settings::bool('feature_wishes'));
        $this->assertSame('Mi', Settings::get('app_name'));
        $this->assertSame('51 102 153', Settings::primaryRgb());
        $this->assertDatabaseHas('audit_logs', ['event' => 'admin.settings.updated', 'user_id' => $admin->id]);
    }

    public function test_unknown_or_invalid_settings_are_rejected(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->patch(route('admin.settings.update'), ['settings' => ['evil_key' => '1']])
            ->assertSessionHasErrors('settings');

        $this->actingAs($admin)->patch(route('admin.settings.update'), ['settings' => ['primary_color' => 'red']])
            ->assertSessionHasErrors('settings.primary_color');
    }

    public function test_disabled_feature_returns_404_and_leaves_the_menu(): void
    {
        $partner = User::factory()->create();
        Settings::set(['feature_wishes' => false]);

        $this->actingAs($partner)->get('/wishes')->assertNotFound();
        $this->actingAs($partner)->get('/dashboard')->assertInertia(
            fn ($page) => $page->where('site.menu', fn ($menu) => collect($menu)->doesntContain('key', 'wishes')),
        );
    }

    public function test_maintenance_mode_blocks_partners_but_not_admins(): void
    {
        $partner = User::factory()->create();
        $admin = $this->admin();
        Settings::set(['maintenance' => true]);

        $this->actingAs($partner)->get('/dashboard')->assertRedirect(route('maintenance'));
        $this->actingAs($partner)->get(route('maintenance'))->assertOk();
        $this->actingAs($admin)->get('/dashboard')->assertOk();
        $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk();
    }

    // ---- notification tester ------------------------------------------------

    public function test_notification_test_reports_users_without_devices(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.notifications.test'), ['title' => 'Szia', 'body' => 'Teszt', 'url' => '/dashboard'])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('flash.type', 'warning');

        $this->assertDatabaseHas('audit_logs', ['event' => 'admin.notification.test']);
    }

    public function test_notification_url_must_be_internal(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.notifications.test'), ['title' => 'x', 'url' => 'https://evil.example'])
            ->assertSessionHasErrors('url');

        $this->actingAs($this->admin())
            ->post(route('admin.notifications.test'), ['title' => 'x', 'url' => '//evil.example'])
            ->assertSessionHasErrors('url');
    }

    // ---- users --------------------------------------------------------------

    public function test_admin_can_edit_name_email_and_password(): void
    {
        $admin = $this->admin();
        $user = User::factory()->create();

        $this->actingAs($admin)->withSession($this->confirmed())
            ->patch(route('admin.users.update', $user), [
                'name' => 'Új Név',
                'email' => 'uj@example.com',
                'password' => 'new-password-123',
                'password_confirmation' => 'new-password-123',
            ])->assertSessionHasNoErrors();

        $user->refresh();
        $this->assertSame('Új Név', $user->name);
        $this->assertSame('uj@example.com', $user->email);
        $this->assertTrue(password_verify('new-password-123', $user->password));
        $this->assertSame('partner', $user->role);

        $log = AuditLog::where('event', 'admin.user.updated')->firstOrFail();
        $this->assertStringNotContainsString('new-password-123', json_encode($log->toArray()));
    }

    public function test_blank_password_keeps_the_old_one_and_role_is_untouched(): void
    {
        $admin = $this->admin();
        $user = User::factory()->create();
        $hash = $user->password;

        $this->actingAs($admin)->withSession($this->confirmed())
            ->patch(route('admin.users.update', $user), [
                'name' => 'Csak név', 'email' => $user->email, 'password' => '', 'role' => 'admin',
            ])->assertSessionHasNoErrors();

        $user->refresh();
        $this->assertSame($hash, $user->password);
        $this->assertSame('partner', $user->role);
    }

    public function test_email_must_stay_unique_and_password_confirmed(): void
    {
        $admin = $this->admin();
        $user = User::factory()->create();

        $this->actingAs($admin)->withSession($this->confirmed())
            ->patch(route('admin.users.update', $user), ['name' => 'X', 'email' => $admin->email])
            ->assertSessionHasErrorsIn('user-'.$user->id, 'email');

        $this->actingAs($admin)->withSession($this->confirmed())
            ->patch(route('admin.users.update', $user), [
                'name' => 'X', 'email' => $user->email, 'password' => 'long-enough-1', 'password_confirmation' => 'different',
            ])->assertSessionHasErrorsIn('user-'.$user->id, 'password');
    }

    public function test_editing_credentials_requires_password_confirmation(): void
    {
        $admin = $this->admin();
        $user = User::factory()->create();

        $this->actingAs($admin)
            ->patch(route('admin.users.update', $user), ['name' => 'Nope', 'email' => $user->email])
            ->assertRedirect(route('password.confirm'));

        $this->assertNotSame('Nope', $user->fresh()->name);
    }

    public function test_password_change_logs_the_user_out_of_other_sessions(): void
    {
        config(['session.driver' => 'database']);

        $admin = $this->admin();
        $user = User::factory()->create();

        DB::table('sessions')->insert([
            'id' => 'victim-session', 'user_id' => $user->id, 'payload' => '', 'last_activity' => time(),
        ]);

        $this->actingAs($admin)->withSession($this->confirmed())
            ->patch(route('admin.users.update', $user), [
                'name' => $user->name, 'email' => $user->email,
                'password' => 'brand-new-pass-1', 'password_confirmation' => 'brand-new-pass-1',
            ])->assertSessionHasNoErrors();

        $this->assertDatabaseMissing('sessions', ['id' => 'victim-session']);
    }
}
