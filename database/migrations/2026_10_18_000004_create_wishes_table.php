<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wishes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete(); // who wishes for it
            $table->string('name');                                         // megnevezés
            $table->text('url');                                            // link (required)
            $table->string('reaction', 20)->nullable();                     // 'consider' = Lehet róla szó | 'decline' = Felejtős
            $table->timestamp('reacted_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wishes');
    }
};