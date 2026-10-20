<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DailySnap;
use App\Models\User;
use App\Services\Audit;
use App\Services\EncryptedFileService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MediaController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'status' => ['nullable', 'in:all,visible,hidden'],
            'user' => ['nullable', 'integer'],
        ]);

        $status = $filters['status'] ?? 'all';

        $snaps = DailySnap::with(['sender:id,name', 'recipient:id,name'])
            ->when($status === 'hidden', fn ($q) => $q->whereNotNull('hidden_at'))
            ->when($status === 'visible', fn ($q) => $q->whereNull('hidden_at'))
            ->when($filters['user'] ?? null, fn ($q, $id) => $q->where('sender_id', $id))
            ->latest('id')
            ->paginate(24)
            ->withQueryString();

        return Inertia::render('Admin/Media', [
            'snaps' => $snaps->through(fn (DailySnap $snap) => [
                'id' => $snap->id,
                'sender' => $snap->sender?->name,
                'recipient' => $snap->recipient?->name,
                'caption' => $snap->caption,
                'mood' => $snap->mood,
                'size' => $snap->file_size,
                'created_at' => $snap->created_at->toIso8601String(),
                'viewed_at' => $snap->viewed_at?->toIso8601String(),
                'hidden_at' => $snap->hidden_at?->toIso8601String(),
                'thumb_url' => route('admin.media.image', ['snap' => $snap->id, 'size' => $snap->thumb_path ? 'thumb' : null], false),
                'image_url' => route('admin.media.image', ['snap' => $snap->id], false),
            ]),
            'filters' => ['status' => $status, 'user' => $filters['user'] ?? null],
            'senders' => User::orderBy('id')->get(['id', 'name']),
            'totals' => [
                'all' => DailySnap::count(),
                'hidden' => DailySnap::whereNotNull('hidden_at')->count(),
                'bytes' => (int) DailySnap::sum('file_size'),
            ],
        ]);
    }

    /** Streams the decrypted image to the admin. Opening the full-size image is audited; grid thumbnails are not. */
    public function image(Request $request, DailySnap $snap, EncryptedFileService $files)
    {
        $thumb = $request->query('size') === 'thumb' && $snap->thumb_path;

        if (! $thumb) {
            Audit::record('admin.media.viewed', "Kép megtekintve (#{$snap->id})", $snap, [
                'sender' => $snap->sender?->name,
                'recipient' => $snap->recipient?->name,
            ]);
        }

        return $thumb
            ? $files->response($snap->thumb_path, 'image/jpeg')
            : $files->response($snap->file_path, $snap->mime_type);
    }

    public function moderate(Request $request, DailySnap $snap): RedirectResponse
    {
        $hide = $request->validate(['hidden' => ['required', 'boolean']])['hidden'];

        $snap->forceFill(['hidden_at' => $hide ? ($snap->hidden_at ?? now()) : null])->save();

        Audit::record(
            $hide ? 'admin.media.hidden' : 'admin.media.restored',
            ($hide ? 'Kép elrejtve' : 'Kép visszaállítva')." (#{$snap->id})",
            $snap,
            ['sender' => $snap->sender?->name],
        );

        return back()->with('flash', ['type' => 'success', 'message' => $hide ? 'A kép el lett rejtve a felhasználók elől.' : 'A kép újra látható.']);
    }

    public function destroy(DailySnap $snap, EncryptedFileService $files): RedirectResponse
    {
        $files->delete($snap->file_path);

        if ($snap->thumb_path) {
            $files->delete($snap->thumb_path);
        }

        Audit::record('admin.media.deleted', "Kép törölve (#{$snap->id})", $snap, [
            'sender' => $snap->sender?->name,
            'recipient' => $snap->recipient?->name,
            'size' => $snap->file_size,
        ]);

        $snap->delete();

        return back()->with('flash', ['type' => 'success', 'message' => 'A kép véglegesen törölve.']);
    }
}
