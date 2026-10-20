<?php

namespace App\Services;

use App\Models\AppSetting;
use Illuminate\Support\Facades\Cache;

/**
 * Admin-editable site settings, stored in the app_settings table.
 * Every setting must be declared in definitions(); that list is also the
 * allow-list used to validate what the admin panel can change.
 */
class Settings
{
    private const CACHE_KEY = 'app_settings.values';

    /** @return array<string, array{type: string, default: mixed, section: string, label: string, hint?: string, max?: int}> */
    public static function definitions(): array
    {
        return [
            'maintenance' => ['type' => 'bool', 'default' => false, 'section' => 'system', 'label' => 'Karbantartási mód', 'hint' => 'Csak az adminok érik el az appot, mindenki más karbantartási oldalt lát.'],
            'maintenance_message' => ['type' => 'string', 'default' => 'Hamarosan visszatérünk! 💗', 'section' => 'system', 'label' => 'Karbantartási üzenet', 'max' => 200],

            'feature_calendar' => ['type' => 'bool', 'default' => true, 'section' => 'features', 'label' => 'Naptár'],
            'feature_snaps' => ['type' => 'bool', 'default' => true, 'section' => 'features', 'label' => 'Pillanatok (képküldés)'],
            'feature_shopping' => ['type' => 'bool', 'default' => true, 'section' => 'features', 'label' => 'Bevásárlólista'],
            'feature_wishes' => ['type' => 'bool', 'default' => true, 'section' => 'features', 'label' => 'Kívánságlista'],
            'feature_push' => ['type' => 'bool', 'default' => true, 'section' => 'features', 'label' => 'Push értesítések', 'hint' => 'Kikapcsolva az app nem küld értesítést (az admin teszt továbbra is működik).'],

            'app_name' => ['type' => 'string', 'default' => 'dateAPP', 'section' => 'ui', 'label' => 'Az app neve', 'max' => 30],
            'primary_color' => ['type' => 'color', 'default' => '', 'section' => 'ui', 'label' => 'Főszín', 'hint' => 'Üresen hagyva az alapértelmezett rózsaszín.'],
            'welcome_text' => ['type' => 'string', 'default' => '', 'section' => 'ui', 'label' => 'Kezdőlap üdvözlő szöveg', 'hint' => 'Üresen hagyva az alapértelmezett szöveg jelenik meg.', 'max' => 140],
            'show_install_card' => ['type' => 'bool', 'default' => true, 'section' => 'ui', 'label' => 'Telepítés kártya'],
            'show_notification_card' => ['type' => 'bool', 'default' => true, 'section' => 'ui', 'label' => 'Értesítések kártya'],
            'show_countdown' => ['type' => 'bool', 'default' => true, 'section' => 'ui', 'label' => 'Következő esemény kártya'],
            'show_tiles' => ['type' => 'bool', 'default' => true, 'section' => 'ui', 'label' => 'Gyorsmenü csempék'],
            'show_burst_button' => ['type' => 'bool', 'default' => true, 'section' => 'ui', 'label' => '„Gondolok rád” gomb'],
        ];
    }

    /** @return array<string, array<string, mixed>> */
    public static function inSection(string $section): array
    {
        return collect(static::definitions())
            ->filter(fn (array $definition) => $definition['section'] === $section)
            ->map(fn (array $definition, string $key) => $definition + ['key' => $key, 'value' => static::get($key)])
            ->values()
            ->all();
    }

    /** @return array<string, mixed> */
    public static function all(): array
    {
        $stored = Cache::rememberForever(self::CACHE_KEY, fn () => AppSetting::query()->pluck('value', 'key')->all());

        $values = [];

        foreach (static::definitions() as $key => $definition) {
            $values[$key] = array_key_exists($key, $stored) && $stored[$key] !== null
                ? static::cast($definition['type'], $stored[$key])
                : $definition['default'];
        }

        return $values;
    }

    public static function get(string $key): mixed
    {
        return static::all()[$key] ?? null;
    }

    public static function bool(string $key): bool
    {
        return (bool) static::get($key);
    }

    /**
     * @param  array<string, mixed>  $values  only keys declared in definitions() are stored
     * @return array<string, array{from: mixed, to: mixed}> what actually changed
     */
    public static function set(array $values): array
    {
        $definitions = static::definitions();
        $current = static::all();
        $changes = [];

        foreach ($values as $key => $value) {
            if (! isset($definitions[$key])) {
                continue;
            }

            $definition = $definitions[$key];
            $value = static::cast($definition['type'], $value);

            if ($value === $current[$key]) {
                continue;
            }

            AppSetting::updateOrCreate(
                ['key' => $key],
                [
                    'value' => is_bool($value) ? ($value ? '1' : '0') : (string) $value,
                    'type' => $definition['type'],
                    'section' => $definition['section'],
                ],
            );

            $changes[$key] = ['from' => $current[$key], 'to' => $value];
        }

        Cache::forget(self::CACHE_KEY);

        return $changes;
    }

    /** "#rrggbb" as the "r g b" triplet the CSS variables use, or null for the default theme. */
    public static function primaryRgb(): ?string
    {
        $hex = (string) static::get('primary_color');

        if (! preg_match('/^#([0-9a-f]{2})([0-9a-f]{2})([0-9a-f]{2})$/i', $hex, $m)) {
            return null;
        }

        return hexdec($m[1]).' '.hexdec($m[2]).' '.hexdec($m[3]);
    }

    private static function cast(string $type, mixed $value): mixed
    {
        return match ($type) {
            'bool' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            default => trim((string) $value),
        };
    }
}
