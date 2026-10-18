<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shopping_items', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('quantity', 50)->nullable();
            $table->boolean('is_checked')->default(false);
            $table->foreignId('added_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('checked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('checked_at')->nullable();
            $table->timestamps();

            $table->index('is_checked');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shopping_items');
    }
};