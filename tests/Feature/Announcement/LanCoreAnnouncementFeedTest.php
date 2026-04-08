<?php

use App\Services\LanCoreAnnouncementFeed;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

beforeEach(function (): void {
    Cache::forget('lancore.announcements.feed');
    config()->set('lancore.announcements_feed_url', 'https://lancore.test/api/announcements/feed');
});

it('returns an array on successful 200 response', function (): void {
    Http::fake([
        'lancore.test/*' => Http::response([
            ['id' => 1, 'audience' => 'all', 'severity' => 'info', 'title' => 'Hello', 'body' => null, 'starts_at' => null, 'ends_at' => null, 'dismissible' => true],
        ], 200),
    ]);

    $result = app(LanCoreAnnouncementFeed::class)->fetch();

    expect($result)->toBeArray()->toHaveCount(1);
});

it('returns an empty array on 500 response', function (): void {
    Http::fake([
        'lancore.test/*' => Http::response('boom', 500),
    ]);

    expect(app(LanCoreAnnouncementFeed::class)->fetch())->toBe([]);
});

it('returns an empty array on exception', function (): void {
    Http::fake(function () {
        throw new RuntimeException('network down');
    });

    expect(app(LanCoreAnnouncementFeed::class)->fetch())->toBe([]);
});

it('caches the result so the second call does not hit the network', function (): void {
    Http::fake([
        'lancore.test/*' => Http::response([], 200),
    ]);

    $service = app(LanCoreAnnouncementFeed::class);
    $service->fetch();
    $service->fetch();

    Http::assertSentCount(1);
});
