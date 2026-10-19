<?php

namespace App\Http\Controllers;

use App\Models\Wish;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WishController extends Controller
{
    public function index(Request $request): Response
    {
        $me = $request->user()->id;

        return Inertia::render('Wishes/Index', [
            'mine' => Wish::where('user_id', $me)->latest()->get()->map->toPayload()->values(),
            'theirs' => Wish::where('user_id', '!=', $me)->latest()->get()->map->toPayload()->values(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $wish = new Wish($this->validated($request));
        $wish->user_id = $request->user()->id;
        $wish->save();

        return back();
    }

    public function update(Request $request, Wish $wish): RedirectResponse
    {
        abort_unless($wish->user_id === $request->user()->id, 403);

        $wish->fill($this->validated($request));

        // A changed name/link is a different wish, so the old reaction no longer applies.
        if ($wish->isDirty(['name', 'url'])) {
            $wish->reaction = null;
            $wish->reacted_at = null;
        }

        $wish->save();

        return back();
    }

    public function destroy(Request $request, Wish $wish): RedirectResponse
    {
        abort_unless($wish->user_id === $request->user()->id, 403);

        $wish->delete();

        return back();
    }

    /** Only the OTHER person may react to a wish. */
    public function react(Request $request, Wish $wish): RedirectResponse
    {
        abort_if($wish->user_id === $request->user()->id, 403);

        $reaction = $request->validate([
            'reaction' => ['nullable', 'in:consider,decline'],
        ])['reaction'] ?? null;

        $wish->forceFill([
            'reaction' => $reaction,
            'reacted_at' => $reaction ? now() : null,
        ])->save();

        return back();
    }

    private function validated(Request $request): array
    {
        // Be forgiving on phones: "example.com/x" becomes "https://example.com/x".
        $url = trim((string) $request->input('url'));
        if ($url !== '' && ! preg_match('#^https?://#i', $url)) {
            $request->merge(['url' => 'https://'.$url]);
        }

        return $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'url' => ['required', 'url:http,https', 'max:2000'],
        ], [], ['name' => 'megnevezés', 'url' => 'link']);
    }
}