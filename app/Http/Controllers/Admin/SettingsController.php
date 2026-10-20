<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Audit;
use App\Services\Settings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $definitions = Settings::definitions();

        $rules = ['settings' => ['required', 'array:'.implode(',', array_keys($definitions))]];

        foreach ($definitions as $key => $definition) {
            $rules["settings.$key"] = match ($definition['type']) {
                'bool' => ['boolean'],
                'color' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
                default => ['nullable', 'string', 'max:'.($definition['max'] ?? 255)],
            };
        }

        $data = $request->validate($rules, [], ['settings.primary_color' => 'főszín']);

        $changes = Settings::set($data['settings']);

        if ($changes) {
            Audit::record(
                'admin.settings.updated',
                'Beállítások módosítva: '.implode(', ', array_keys($changes)),
                null,
                ['changes' => $changes],
            );
        }

        return back()->with('flash', ['type' => 'success', 'message' => $changes ? 'Beállítások mentve.' : 'Nem történt változás.']);
    }
}
