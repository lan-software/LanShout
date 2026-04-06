<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chat_settings', function (Blueprint $table) {
            $table->id();

            // Content filtering
            $table->jsonb('blocked_words')->default('[]');
            $table->jsonb('regex_filters')->default('[]');
            $table->string('filter_action', 10)->default('censor');
            $table->boolean('allow_urls')->default(true);

            // Spam detection
            $table->integer('spam_repeat_threshold')->default(3);
            $table->integer('spam_window_seconds')->default(60);

            // Rate limiting
            $table->integer('rate_limit_messages')->default(5);
            $table->integer('rate_limit_window_seconds')->default(60);

            // Slow mode
            $table->boolean('slow_mode_enabled')->default(false);
            $table->integer('slow_mode_cooldown_seconds')->default(10);
            $table->boolean('slow_mode_auto_enabled')->default(false);
            $table->integer('slow_mode_auto_threshold')->default(50);

            $table->timestamps();
        });

        DB::table('chat_settings')->insert([
            'blocked_words' => '[]',
            'regex_filters' => '[]',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_settings');
    }
};
