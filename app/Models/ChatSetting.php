<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class ChatSetting extends Model
{
    protected $attributes = [
        'blocked_words' => '[]',
        'regex_filters' => '[]',
        'profanity_filter_enabled' => true,
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
        'profanity_filter_enabled',
    ];

    protected function casts(): array
    {
        return [
            'blocked_words' => 'array',
            'regex_filters' => 'array',
            'allow_urls' => 'boolean',
            'slow_mode_enabled' => 'boolean',
            'slow_mode_auto_enabled' => 'boolean',
            'profanity_filter_enabled' => 'boolean',
        ];
    }

    /**
     * Get all blocked words: admin-defined list plus every safety preset.
     * Non-safety presets (profanity/sexual/ldnoobw) are included only when
     * the profanity filter toggle is enabled.
     */
    public function effectiveBlockedWords(): array
    {
        $words = $this->blocked_words ?? [];
        $profanityEnabled = $this->profanity_filter_enabled ?? true;

        foreach (config('chat-filters', []) as $preset) {
            $isSafety = $preset['safety'] ?? false;
            if (! $isSafety && ! $profanityEnabled) {
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
                'profanity_filter_enabled' => true,
            ]);
        });
    }

    public function clearCache(): void
    {
        Cache::forget('chat_settings');
    }
}
