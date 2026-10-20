<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MenuItem;
use App\Services\Audit;
use App\Services\Settings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class CmsController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Cms', [
            'ui' => Settings::inSection('ui'),
            'menu' => MenuItem::orderBy('sort_order')->orderBy('id')->get()->map(fn (MenuItem $item) => [
                'id' => $item->id,
                'key' => $item->key,
                'label' => $item->label,
                'icon' => $item->icon,
                'href' => $item->href,
                'visible' => $item->is_visible,
                'featured' => $item->is_featured,
            ]),
            'icons' => MenuItem::ICONS,
            'maxVisible' => MenuItem::MAX_VISIBLE,
        ]);
    }

    /** Saves the whole menu at once: order = array order; custom links missing from the list are deleted. */
    public function updateMenu(Request $request): RedirectResponse
    {
        $builtInIds = MenuItem::whereNotNull('key')->pluck('id')->all();

        $data = $request->validate([
            'items' => ['required', 'array', 'min:1', 'max:12'],
            'items.*.id' => ['nullable', 'integer', Rule::exists('menu_items', 'id')],
            'items.*.label' => ['required', 'string', 'max:30'],
            'items.*.icon' => ['required', Rule::in(MenuItem::ICONS)],
            'items.*.href' => ['nullable', 'string', 'max:200', 'regex:/^\/(?!\/)[\w\-\/.?=&%#]*$/'],
            'items.*.visible' => ['required', 'boolean'],
            'items.*.featured' => ['required', 'boolean'],
        ], [
            'items.*.href.regex' => 'A hivatkozás csak az appon belüli útvonal lehet, pl. /dashboard.',
        ]);

        $items = collect($data['items']);

        $validator = validator([], []);

        $validator->after(function ($validator) use ($items, $builtInIds) {
            $sentIds = $items->pluck('id')->filter()->all();

            if (array_diff($builtInIds, $sentIds)) {
                $validator->errors()->add('items', 'Az alapmenüpontok nem törölhetők.');
            }

            if (count($sentIds) !== count(array_unique($sentIds))) {
                $validator->errors()->add('items', 'Egy menüpont csak egyszer szerepelhet.');
            }

            $visible = $items->where('visible', true)->count();

            if ($visible < 1 || $visible > MenuItem::MAX_VISIBLE) {
                $validator->errors()->add('items', 'Legalább 1, legfeljebb '.MenuItem::MAX_VISIBLE.' menüpont lehet látható.');
            }

            if ($items->where('featured', true)->count() > 1) {
                $validator->errors()->add('items', 'Csak egy menüpont lehet kiemelt.');
            }

            foreach ($items as $index => $item) {
                if (empty($item['id']) && empty($item['href'])) {
                    $validator->errors()->add("items.$index.href", 'Az új menüponthoz hivatkozás kell.');
                }
            }
        });

        $validator->validate();

        DB::transaction(function () use ($items) {
            $keep = [];

            foreach ($items->values() as $order => $item) {
                $attributes = [
                    'label' => trim($item['label']),
                    'icon' => $item['icon'],
                    'sort_order' => $order,
                    'is_visible' => $item['visible'],
                    'is_featured' => $item['featured'],
                ];

                if (! empty($item['id'])) {
                    $model = MenuItem::findOrFail($item['id']);

                    if (! $model->isBuiltIn()) {
                        $attributes['href'] = $item['href'] ?: $model->href; // built-in targets are fixed
                    }

                    $model->update($attributes);
                } else {
                    $model = MenuItem::create($attributes + ['href' => $item['href']]);
                }

                $keep[] = $model->id;
            }

            MenuItem::whereNull('key')->whereNotIn('id', $keep)->delete();
        });

        MenuItem::forget();

        Audit::record('admin.menu.updated', 'Menü módosítva', null, [
            'items' => $items->map(fn (array $i) => $i['label'].($i['visible'] ? '' : ' (rejtve)'))->all(),
        ]);

        return back()->with('flash', ['type' => 'success', 'message' => 'Menü mentve.']);
    }
}
