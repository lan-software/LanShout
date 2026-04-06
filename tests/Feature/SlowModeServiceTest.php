<?php

use App\Models\ChatSetting;
use App\Services\SlowModeService;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    Cache::flush();
});

test('slow mode is inactive by default', function () {
    ChatSetting::create([
        'blocked_words' => [],
        'regex_filters' => [],
        'slow_mode_enabled' => false,
        'slow_mode_auto_enabled' => false,
        'slow_mode_auto_threshold' => 50,
        'slow_mode_cooldown_seconds' => 5,
    ]);

    $service = new SlowModeService();

    expect($service->isSlowModeActive())->toBeFalse();
});

test('slow mode is active when manually enabled', function () {
    ChatSetting::create([
        'blocked_words' => [],
        'regex_filters' => [],
        'slow_mode_enabled' => true,
        'slow_mode_auto_enabled' => false,
        'slow_mode_auto_threshold' => 50,
        'slow_mode_cooldown_seconds' => 5,
    ]);

    $service = new SlowModeService();

    expect($service->isSlowModeActive())->toBeTrue();
});

test('user can send when no recent message', function () {
    ChatSetting::create([
        'blocked_words' => [],
        'regex_filters' => [],
        'slow_mode_enabled' => true,
        'slow_mode_auto_enabled' => false,
        'slow_mode_auto_threshold' => 50,
        'slow_mode_cooldown_seconds' => 10,
    ]);

    $service = new SlowModeService();

    expect($service->canUserSend(999))->toBeTrue();
});

test('user is blocked during cooldown period', function () {
    ChatSetting::create([
        'blocked_words' => [],
        'regex_filters' => [],
        'slow_mode_enabled' => true,
        'slow_mode_auto_enabled' => false,
        'slow_mode_auto_threshold' => 50,
        'slow_mode_cooldown_seconds' => 30,
    ]);

    $service = new SlowModeService();
    $service->recordMessage(42);

    $result = $service->canUserSend(42);

    expect($result)->toBeInt();
    expect($result)->toBeGreaterThan(0);
    expect($result)->toBeLessThanOrEqual(30);
});

test('cooldown expires correctly', function () {
    ChatSetting::create([
        'blocked_words' => [],
        'regex_filters' => [],
        'slow_mode_enabled' => true,
        'slow_mode_auto_enabled' => false,
        'slow_mode_auto_threshold' => 50,
        'slow_mode_cooldown_seconds' => 2,
    ]);

    $service = new SlowModeService();

    // Record a message with a timestamp in the past beyond the cooldown
    Cache::put('slow-mode:42', now()->timestamp - 3, 2);

    // Cooldown should have expired
    expect($service->canUserSend(42))->toBeTrue();
});
