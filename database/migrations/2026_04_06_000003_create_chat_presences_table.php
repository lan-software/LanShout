<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chat_presences', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('last_seen_at');

            $table->unique('user_id');
            $table->index('last_seen_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_presences');
    }
};
