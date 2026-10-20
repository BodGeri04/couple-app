<?php

namespace App\Http\Controllers;

use App\Models\DailySnap;
use App\Services\EncryptedFileService;
use App\Services\PushService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

use function Illuminate\Support\defer;

class SnapController extends Controller
{
    public function index(Request $request): Response
    {
        $me = $request->user();

        $snaps = DailySnap::with('sender:id,name')
            ->visible()
            ->where(fn ($query) => $query->where('sender_id', $me->id)->orWhere('recipient_id', $me->id))
            ->latest()
            ->limit(60)
            ->get();

        return Inertia::render('Snaps/Index', [
            'snaps' => $snaps->map(fn (DailySnap $snap) => $snap->toPayload($me))->values(),
            'openId' => $request->integer('open') ?: null,
        ]);
    }

    public function store(Request $request, EncryptedFileService $files, PushService $push): RedirectResponse
    {
        $me = $request->user();
        $partner = $me->partner();

        if (! $partner) {
            return back()->withErrors(['photo' => 'Nincs kinek elküldeni.']);
        }

        $data = $request->validate([
            'photo' => ['required', 'file', 'image', 'mimes:jpeg,png,webp', 'max:12288'],
            'thumb' => ['nullable', 'file', 'image', 'mimes:jpeg,png,webp', 'max:2048'],
            'caption' => ['nullable', 'string', 'max:140'],
            'mood' => ['nullable', 'string', 'max:8'],
            'reply_to_id' => ['nullable', 'integer'],
        ], [], ['photo' => 'kép', 'caption' => 'felirat']);

        // A reply must point at a snap that was sent TO me.
        $replyTo = isset($data['reply_to_id'])
            ? DailySnap::where('id', $data['reply_to_id'])->where('recipient_id', $me->id)->value('id')
            : null;

        // Encrypted before it ever touches the disk.
        $photo = $files->store($request->file('photo'));
        $thumb = $request->hasFile('thumb') ? $files->store($request->file('thumb')) : null;

        $snap = new DailySnap([
            'caption' => $data['caption'] ?? null,
            'mood' => $data['mood'] ?? null,
        ]);
        $snap->sender_id = $me->id;
        $snap->recipient_id = $partner->id;
        $snap->reply_to_id = $replyTo;
        $snap->file_path = $photo['path'];
        $snap->mime_type = $photo['mime_type'];
        $snap->file_size = $photo['size'];
        $snap->thumb_path = $thumb['path'] ?? null;
        $snap->save();

        // Runs after the response is sent, so the upload feels instant.
        defer(fn () => $push->sendToUser($partner, [
            'title' => $replyTo ? "{$me->name} válaszolt 💬" : "{$me->name} küldött neked egy pillanatot 📸",
            'body' => $snap->caption ?: 'Koppints a megnyitáshoz 💗',
            'url' => '/snaps?open='.$snap->id,
            'tag' => 'snap-'.$snap->id,
        ]));

        return back();
    }

    /** Decrypts in memory and streams the image, only to the sender or recipient. */
    public function image(Request $request, DailySnap $snap, EncryptedFileService $files)
    {
        abort_unless(in_array($request->user()->id, [$snap->sender_id, $snap->recipient_id], true), 403);
        abort_if($snap->hidden_at, 404); // removed by moderation

        if ($request->query('size') === 'thumb' && $snap->thumb_path) {
            return $files->response($snap->thumb_path, 'image/jpeg');
        }

        return $files->response($snap->file_path, $snap->mime_type);
    }

    public function seen(Request $request, DailySnap $snap): JsonResponse
    {
        if ($snap->recipient_id === $request->user()->id && ! $snap->viewed_at) {
            $snap->forceFill(['viewed_at' => now()])->save();
        }

        return response()->json(['ok' => true]);
    }

    public function destroy(Request $request, DailySnap $snap, EncryptedFileService $files): RedirectResponse
    {
        abort_unless($snap->sender_id === $request->user()->id, 403);

        $files->delete($snap->file_path);

        if ($snap->thumb_path) {
            $files->delete($snap->thumb_path);
        }

        $snap->delete();

        return back();
    }
}