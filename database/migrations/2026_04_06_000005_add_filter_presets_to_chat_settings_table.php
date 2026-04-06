<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chat_settings', function (Blueprint $table) {
            $table->jsonb('active_filter_presets')->default('[]');
            $table->boolean('nsfw_mode')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('chat_settings', function (Blueprint $table) {
            $table->dropColumn(['active_filter_presets', 'nsfw_mode']);
        });
    }
};
