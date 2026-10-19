<?php

namespace App\Http\Controllers;

use App\Models\ShoppingItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ShoppingController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Shopping/Index', [
            'items' => ShoppingItem::with(['adder:id,name', 'checker:id,name'])
                ->orderBy('created_at')
                ->get()
                ->map->toPayload()
                ->values(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'quantity' => ['nullable', 'string', 'max:30'],
        ], [], ['name' => 'tétel neve', 'quantity' => 'mennyiség']);

        $item = new ShoppingItem($data);
        $item->added_by = $request->user()->id;
        $item->save();

        return back();
    }

    public function toggle(Request $request, ShoppingItem $item): RedirectResponse
    {
        $checked = ! $item->is_checked;

        $item->forceFill([
            'is_checked' => $checked,
            'checked_by' => $checked ? $request->user()->id : null,
            'checked_at' => $checked ? now() : null,
        ])->save();

        return back();
    }

    public function destroy(ShoppingItem $item): RedirectResponse
    {
        $item->delete();

        return back();
    }

    public function clear(): RedirectResponse
    {
        ShoppingItem::where('is_checked', true)->delete();

        return back();
    }
}