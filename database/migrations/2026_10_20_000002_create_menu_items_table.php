<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('menu_items', function (Blueprint $table) {
            $table->id();
            $table->string('key', 30)->nullable()->unique(); // set for built-in items, null for custom links
            $table->string('label', 30);
            $table->string('icon', 40);
            $table->string('href');
            $table->string('feature', 30)->nullable();       // hidden automatically when this feature is switched off
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_visible')->default(true);
            $table->boolean('is_featured')->default(false);  // the raised center button
            $table->timestamps();
        });

        $now = now();

        DB::table('menu_items')->insert([
            ['key' => 'dashboard', 'label' => 'Kezdőlap', 'icon' => 'House', 'href' => '/dashboard', 'feature' => null, 'sort_order' => 0, 'is_visible' => true, 'is_featured' => false],
            ['key' => 'calendar', 'label' => 'Naptár', 'icon' => 'CalendarHeart', 'href' => '/calendar', 'feature' => 'calendar', 'sort_order' => 1, 'is_visible' => true, 'is_featured' => false],
            ['key' => 'snaps', 'label' => 'Pillanat', 'icon' => 'Camera', 'href' => '/snaps', 'feature' => 'snaps', 'sort_order' => 2, 'is_visible' => true, 'is_featured' => true],
            ['key' => 'shopping', 'label' => 'Bevásárlás', 'icon' => 'ShoppingBasket', 'href' => '/shopping', 'feature' => 'shopping', 'sort_order' => 3, 'is_visible' => true, 'is_featured' => false],
            ['key' => 'wishes', 'label' => 'Kívánságok', 'icon' => 'Gift', 'href' => '/wishes', 'feature' => 'wishes', 'sort_order' => 4, 'is_visible' => true, 'is_featured' => false],
        ]);

        DB::table('menu_items')->update(['created_at' => $now, 'updated_at' => $now]);
    }

    public function down(): void
    {
        Schema::dropIfExists('menu_items');
    }
};
