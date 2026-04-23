<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chat_settings', function (Blueprint $table) {
            $table->boolean('profanity_filter_enabled')->default(true);
        });

        // Carry prior intent forward: rows that previously had nsfw_mode=true
        // meant "don't apply profanity/sexual/ldnoobw presets", which maps to
        // profanity_filter_enabled=false.
        DB::table('chat_settings')
            ->where('nsfw_mode', true)
            ->update(['profanity_filter_enabled' => false]);

        Schema::table('chat_settings', function (Blueprint $table) {
            $table->dropColumn(['active_filter_presets', 'nsfw_mode']);
        });
    }

    public function down(): void
    {
        Schema::table('chat_settings', function (Blueprint $table) {
            $table->jsonb('active_filter_presets')->default('[]');
            $table->boolean('nsfw_mode')->default(false);
        });

        DB::table('chat_settings')
            ->where('profanity_filter_enabled', false)
            ->update(['nsfw_mode' => true]);

        Schema::table('chat_settings', function (Blueprint $table) {
            $table->dropColumn('profanity_filter_enabled');
        });
    }
};
