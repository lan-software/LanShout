<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class ChatSetting extends Model
{
    protected $attributes = [
        'blocked_words' => '[]',
        'regex_filters' => '[]',
        'active_filter_presets' => '[]',
        'nsfw_mode' => false,
    ];

    protected $fillable = [
        'blocked_words',
        'regex_filters',
        'filter_action',
        'allow_urls',
        'spam_repeat_threshold',
        'spam_window_seconds',
        'rate_limit_messages',
        'rate_limit_window_seconds',
        'slow_mode_enabled',
        'slow_mode_cooldown_seconds',
        'slow_mode_auto_enabled',
        'slow_mode_auto_threshold',
        'active_filter_presets',
        'nsfw_mode',
    ];

    protected function casts(): array
    {
        return [
            'blocked_words' => 'array',
            'regex_filters' => 'array',
            'allow_urls' => 'boolean',
            'slow_mode_enabled' => 'boolean',
            'slow_mode_auto_enabled' => 'boolean',
            'active_filter_presets' => 'array',
            'nsfw_mode' => 'boolean',
        ];
    }

    /**
     * Get all blocked words: custom list + words from active filter presets.
     * In NSFW mode, only presets marked `always_active_in_nsfw` contribute.
     */
    public function effectiveBlockedWords(): array
    {
        $words = $this->blocked_words ?? [];

        $presets = config('chat-filters', []);
        $activePresets = $this->active_filter_presets ?? [];

        foreach ($activePresets as $presetKey) {
            if (! isset($presets[$presetKey])) {
                continue;
            }

            $preset = $presets[$presetKey];

            // In NSFW mode, skip presets that are not always-active
            if ($this->nsfw_mode && ! ($preset['always_active_in_nsfw'] ?? false)) {
                continue;
            }

            $words = array_merge($words, $preset['words'] ?? []);
        }

        return array_unique($words);
    }

    public static function current(): self
    {
        return Cache::remember('chat_settings', 60, function () {
            return self::firstOrCreate([], [
                'blocked_words' => [],
                'regex_filters' => [],
                'active_filter_presets' => [],
                'nsfw_mode' => false,
            ]);
        });
    }

    public function clearCache(): void
    {
        Cache::forget('chat_settings');
    }
}
