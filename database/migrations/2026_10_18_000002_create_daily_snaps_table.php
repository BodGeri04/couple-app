<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_snaps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sender_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('recipient_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('reply_to_id')->nullable()->constrained('daily_snaps')->nullOnDelete();
            $table->string('file_path');          // path of the ENCRYPTED file (private disk)
            $table->string('mime_type', 50);      // original image type, needed to serve it back
            $table->unsignedInteger('file_size')->nullable(); // original (decrypted) size in bytes
            $table->string('caption', 140)->nullable();
            $table->string('mood', 16)->nullable();           // emoji
            $table->timestamp('viewed_at')->nullable();
            $table->timestamps();

            $table->index(['recipient_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_snaps');
    }
};