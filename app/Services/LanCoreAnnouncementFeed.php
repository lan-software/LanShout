<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

class LanCoreAnnouncementFeed
{
    private const CACHE_KEY = 'lancore.announcements.feed';

    private const CACHE_TTL_SECONDS = 60;

    /**
     * Fetch the active announcement feed from LanCore.
     *
     * Returns an empty array on any failure (timeout, non-2xx, JSON error, exception).
     *
     * @return array<int, array<string, mixed>>
     */
    public function fetch(): array
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL_SECONDS, function (): array {
            try {
                $url = (string) config('lancore.announcements_feed_url');

                if ($url === '') {
                    return [];
                }

                $response = Http::timeout(2)->retry(1, 100)->get($url);

                if (! $response->successful()) {
                    return [];
                }

                $data = $response->json();

                return is_array($data) ? $data : [];
            } catch (Throwable) {
                return [];
            }
        });
    }
}
