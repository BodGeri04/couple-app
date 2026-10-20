<?php

namespace Tests\Feature\Admin;

use App\Models\DailySnap;
use App\Models\MenuItem;
use App\Models\User;
use App\Services\EncryptedFileService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminContentTest extends TestCase
{
    use RefreshDatabase;

    // ---- CMS menu -----------------------------------------------------------

    private function menuPayload(?callable $mutate = null): array
    {
        $items = MenuItem::orderBy('sort_order')->get()->map(fn (MenuItem $i) => [
            'id' => $i->id, 'label' => $i->label, 'icon' => $i->icon, 'href' => $i->href,
            'visible' => $i->is_visible, 'featured' => $i->is_featured,
        ])->all();

        return ['items' => $mutate ? $mutate($items) : $items];
    }

    public function test_menu_ships_with_the_five_built_in_items(): void
    {
        $this->assertSame(['dashboard', 'calendar', 'snaps', 'shopping', 'wishes'], MenuItem::orderBy('sort_order')->pluck('key')->all());
    }

    public function test_admin_can_rename_reorder_hide_and_add_menu_items(): void
    {
        $admin = User::factory()->admin()->create();

        $payload = $this->menuPayload(function (array $items) {
            $items[0]['label'] = 'Otthon';
            $items[1]['visible'] = false;
            $items = array_reverse($items);
            $items[] = ['id' => null, 'label' => 'Blog', 'icon' => 'Star', 'href' => '/blog', 'visible' => false, 'featured' => false];

            return $items;
        });

        $this->actingAs($admin)->put(route('admin.cms.menu'), $payload)->assertSessionHasNoErrors();

        $this->assertSame('Otthon', MenuItem::where('key', 'dashboard')->value('label'));
        $this->assertSame('wishes', MenuItem::orderBy('sort_order')->first()->key);
        $this->assertDatabaseHas('menu_items', ['label' => 'Blog', 'href' => '/blog', 'key' => null]);
        $this->assertDatabaseHas('audit_logs', ['event' => 'admin.menu.updated']);

        $nav = collect(MenuItem::navigation());
        $this->assertTrue($nav->contains('label', 'Otthon'));
        $this->assertFalse($nav->contains('key', 'calendar'));
    }

    public function test_custom_items_can_be_deleted_but_built_ins_cannot(): void
    {
        $admin = User::factory()->admin()->create();
        $custom = MenuItem::create(['label' => 'Ideiglenes', 'icon' => 'Star', 'href' => '/x', 'sort_order' => 9, 'is_visible' => false, 'is_featured' => false]);

        $this->actingAs($admin)->put(route('admin.cms.menu'), $this->menuPayload(fn (array $items) => array_values(array_filter($items, fn ($i) => $i['id'] !== $custom->id))))
            ->assertSessionHasNoErrors();
        $this->assertModelMissing($custom);

        $this->actingAs($admin)->put(route('admin.cms.menu'), $this->menuPayload(fn (array $items) => array_slice($items, 1)))
            ->assertSessionHasErrors('items');
        $this->assertSame(5, MenuItem::whereNotNull('key')->count());
    }

    public function test_menu_validation_rules(): void
    {
        $admin = User::factory()->admin()->create();

        // too many visible
        $this->actingAs($admin)->put(route('admin.cms.menu'), $this->menuPayload(function (array $items) {
            $items[] = ['id' => null, 'label' => 'Hatodik', 'icon' => 'Star', 'href' => '/x', 'visible' => true, 'featured' => false];

            return $items;
        }))->assertSessionHasErrors('items');

        // external link, unknown icon
        $this->actingAs($admin)->put(route('admin.cms.menu'), $this->menuPayload(function (array $items) {
            $items[] = ['id' => null, 'label' => 'Rossz', 'icon' => 'Star', 'href' => '//evil.example', 'visible' => false, 'featured' => false];

            return $items;
        }))->assertSessionHasErrors('items.5.href');

        $this->actingAs($admin)->put(route('admin.cms.menu'), $this->menuPayload(function (array $items) {
            $items[0]['icon'] = 'Skull';

            return $items;
        }))->assertSessionHasErrors('items.0.icon');
    }

    // ---- Media moderation ---------------------------------------------------

    private function snap(User $from, User $to): DailySnap
    {
        Storage::fake('local');

        $file = UploadedFile::fake()->image('p.jpg', 20, 20);
        $stored = app(EncryptedFileService::class)->store($file);

        $snap = new DailySnap(['caption' => 'hello']);
        $snap->sender_id = $from->id;
        $snap->recipient_id = $to->id;
        $snap->file_path = $stored['path'];
        $snap->mime_type = $stored['mime_type'];
        $snap->file_size = $stored['size'];
        $snap->save();

        return $snap;
    }

    public function test_admin_can_view_hide_restore_and_delete_snaps(): void
    {
        $admin = User::factory()->admin()->create();
        [$a, $b] = [User::factory()->create(), User::factory()->create()];
        $snap = $this->snap($a, $b);

        $this->actingAs($admin)->get(route('admin.media.index'))->assertOk()
            ->assertInertia(fn ($page) => $page->has('snaps.data', 1));

        $this->actingAs($admin)->get(route('admin.media.image', $snap))
            ->assertOk()->assertHeader('Content-Type', 'image/jpeg');
        $this->assertDatabaseHas('audit_logs', ['event' => 'admin.media.viewed']);

        // moderation hides it from the users themselves
        $this->actingAs($admin)->patch(route('admin.media.moderate', $snap), ['hidden' => true])->assertSessionHasNoErrors();
        $this->assertNotNull($snap->fresh()->hidden_at);
        $this->actingAs($b)->get(route('snaps.image', $snap))->assertNotFound();
        $this->actingAs($b)->get(route('snaps.index'))->assertInertia(fn ($page) => $page->has('snaps', 0));

        $this->actingAs($admin)->patch(route('admin.media.moderate', $snap), ['hidden' => false]);
        $this->assertNull($snap->fresh()->hidden_at);
        $this->actingAs($b)->get(route('snaps.image', $snap))->assertOk();

        $path = $snap->file_path;
        $this->actingAs($admin)->delete(route('admin.media.destroy', $snap))->assertSessionHasNoErrors();
        $this->assertModelMissing($snap);
        Storage::disk('local')->assertMissing($path);
        $this->assertDatabaseHas('audit_logs', ['event' => 'admin.media.deleted']);
    }

    public function test_partners_cannot_reach_the_admin_media_endpoints(): void
    {
        [$a, $b] = [User::factory()->create(), User::factory()->create()];
        $snap = $this->snap($a, $b);

        $this->actingAs($a)->get(route('admin.media.image', $snap))->assertForbidden();
        $this->actingAs($a)->patch(route('admin.media.moderate', $snap), ['hidden' => true])->assertForbidden();
        $this->actingAs($a)->delete(route('admin.media.destroy', $snap))->assertForbidden();
        $this->assertModelExists($snap);
    }
}
