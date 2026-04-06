<?php

namespace App\Services;

use App\Models\ChatSetting;
use App\Models\Message;
use Illuminate\Support\Facades\Cache;

class SlowModeService
{
    public function isSlowModeActive(): bool
    {
        $settings = ChatSetting::current();

        if ($settings->slow_mode_enabled) {
            return true;
        }

        if ($settings->slow_mode_auto_enabled) {
            return $this->shouldAutoEnable($settings);
        }

        return false;
    }

    /**
     * @return true|int True if user can send, or remaining cooldown seconds.
     */
    public function canUserSend(int $userId): true|int
    {
        if (! $this->isSlowModeActive()) {
            return true;
        }

        $settings = ChatSetting::current();
        $lastSent = Cache::get("slow-mode:{$userId}");

        if ($lastSent === null) {
            return true;
        }

        $remaining = $settings->slow_mode_cooldown_seconds - (now()->timestamp - $lastSent);

        return $remaining > 0 ? $remaining : true;
    }

    public function recordMessage(int $userId): void
    {
        $settings = ChatSetting::current();
        Cache::put(
            "slow-mode:{$userId}",
            now()->timestamp,
            $settings->slow_mode_cooldown_seconds,
        );
    }

    private function shouldAutoEnable(ChatSetting $settings): bool
    {
        $recentCount = Message::where('created_at', '>=', now()->subMinute())->count();

        return $recentCount >= $settings->slow_mode_auto_threshold;
    }
}
