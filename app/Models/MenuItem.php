<?php

namespace App\Models;

use App\Services\Settings;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class MenuItem extends Model
{
    /** Icons the admin can pick from (must also exist in resources/js/lib/icons.js). */
    public const ICONS = [
        'House', 'CalendarHeart', 'Camera', 'ShoppingBasket', 'Gift', 'Heart', 'Star', 'Bell',
        'Image', 'Music', 'MapPin', 'Sparkles', 'MessageCircle', 'BookHeart', 'Coffee', 'Plane',
    ];

    public const MAX_VISIBLE = 5;

    private const CACHE_KEY = 'menu_items.navigation';

    protected $fillable = ['label', 'icon', 'href', 'sort_order', 'is_visible', 'is_featured'];

    protected function casts(): array
    {
        return [
            'is_visible' => 'boolean',
            'is_featured' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function isBuiltIn(): bool
    {
        return $this->key !== null;
    }

    public static function forget(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /** The bottom navigation as the app shell renders it. */
    public static function navigation(): array
    {
        $items = Cache::rememberForever(self::CACHE_KEY, fn () => static::query()
            ->where('is_visible', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(fn (self $item) => [
                'key' => $item->key,
                'label' => $item->label,
                'icon' => $item->icon,
                'href' => $item->href,
                'feature' => $item->feature,
                'featured' => $item->is_featured,
            ])
            ->all());

        return collect($items)
            ->reject(fn (array $item) => $item['feature'] && ! Settings::bool('feature_'.$item['feature']))
            ->map(fn (array $item) => collect($item)->except('feature')->all())
            ->values()
            ->all();
    }
}
