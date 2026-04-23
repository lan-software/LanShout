<?php

use App\Models\ChatSetting;
use App\Models\Role;
use App\Models\User;

function createChatSettingsUserWithRole(string $roleName): User
{
    $role = Role::firstOrCreate(['name' => $roleName], ['display_name' => ucfirst(str_replace('_', ' ', $roleName))]);
    $user = User::factory()->create();
    $user->roles()->attach($role);

    return $user;
}

function seedChatSettings(array $overrides = []): ChatSetting
{
    return ChatSetting::create(array_merge([
        'blocked_words' => [],
        'regex_filters' => [],
        'filter_action' => 'block',
        'allow_urls' => true,
        'spam_repeat_threshold' => 3,
        'spam_window_seconds' => 60,
        'rate_limit_messages' => 10,
        'rate_limit_window_seconds' => 60,
        'slow_mode_enabled' => false,
        'slow_mode_cooldown_seconds' => 5,
        'slow_mode_auto_enabled' => false,
        'slow_mode_auto_threshold' => 50,
    ], $overrides));
}

// --- ChatSettingsController@index ---

test('admin can view chat settings page', function () {
    $admin = createChatSettingsUserWithRole('admin');
    seedChatSettings();

    $response = $this->actingAs($admin)->get(route('admin.chat-settings.index'));

    $response->assertOk();
});

test('moderator can view chat settings page', function () {
    $moderator = createChatSettingsUserWithRole('moderator');
    seedChatSettings();

    $response = $this->actingAs($moderator)->get(route('admin.chat-settings.index'));

    $response->assertOk();
});

test('regular user gets 403 on chat settings', function () {
    $user = User::factory()->create();
    seedChatSettings();

    $response = $this->actingAs($user)->get(route('admin.chat-settings.index'));

    $response->assertForbidden();
});

test('guest is redirected from chat settings', function () {
    seedChatSettings();

    $response = $this->get(route('admin.chat-settings.index'));

    $response->assertRedirect(route('login'));
});

// --- ChatSettingsController@update ---

test('admin can update chat settings', function () {
    $admin = createChatSettingsUserWithRole('admin');
    seedChatSettings();

    $response = $this->actingAs($admin)->put(route('admin.chat-settings.update'), [
        'blocked_words' => ['badword'],
        'regex_filters' => [],
        'filter_action' => 'censor',
        'allow_urls' => false,
        'spam_repeat_threshold' => 5,
        'spam_window_seconds' => 120,
        'rate_limit_messages' => 20,
        'rate_limit_window_seconds' => 30,
        'slow_mode_enabled' => true,
        'slow_mode_cooldown_seconds' => 10,
        'slow_mode_auto_enabled' => false,
        'slow_mode_auto_threshold' => 50,
        'profanity_filter_enabled' => true,
    ]);

    $response->assertRedirect();

    $settings = ChatSetting::first();
    expect($settings->blocked_words)->toBe(['badword']);
    expect($settings->filter_action)->toBe('censor');
    expect($settings->allow_urls)->toBeFalse();
    expect($settings->spam_repeat_threshold)->toBe(5);
    expect($settings->slow_mode_enabled)->toBeTrue();
});

test('moderator cannot update chat settings', function () {
    $moderator = createChatSettingsUserWithRole('moderator');
    seedChatSettings();

    $response = $this->actingAs($moderator)->put(route('admin.chat-settings.update'), [
        'blocked_words' => [],
        'regex_filters' => [],
        'filter_action' => 'block',
        'allow_urls' => true,
        'spam_repeat_threshold' => 3,
        'spam_window_seconds' => 60,
        'rate_limit_messages' => 10,
        'rate_limit_window_seconds' => 60,
        'slow_mode_enabled' => false,
        'slow_mode_cooldown_seconds' => 5,
        'slow_mode_auto_enabled' => false,
        'slow_mode_auto_threshold' => 50,
        'profanity_filter_enabled' => true,
    ]);

    $response->assertForbidden();
});

test('validation rejects invalid chat settings', function () {
    $admin = createChatSettingsUserWithRole('admin');
    seedChatSettings();

    $response = $this->actingAs($admin)->putJson(route('admin.chat-settings.update'), [
        'blocked_words' => [],
        'regex_filters' => [],
        'filter_action' => 'invalid_action',
        'allow_urls' => true,
        'spam_repeat_threshold' => 0, // min:1 violation
        'spam_window_seconds' => 5,   // min:10 violation
        'rate_limit_messages' => 0,   // min:1 violation
        'rate_limit_window_seconds' => 60,
        'slow_mode_enabled' => false,
        'slow_mode_cooldown_seconds' => 5,
        'slow_mode_auto_enabled' => false,
        'slow_mode_auto_threshold' => 50,
        'profanity_filter_enabled' => true,
    ]);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['filter_action', 'spam_repeat_threshold', 'spam_window_seconds', 'rate_limit_messages']);
});

test('safety presets are always included in effective blocked words', function () {
    $settings = seedChatSettings(['profanity_filter_enabled' => false]);

    $words = $settings->effectiveBlockedWords();

    // A sampling from each safety preset.
    expect($words)->toContain('pedophile');
    expect($words)->toContain('bestiality');
    expect($words)->toContain('white power');
    expect($words)->toContain('kys');
});

test('profanity presets are gated by profanity_filter_enabled', function () {
    $enabled = seedChatSettings(['profanity_filter_enabled' => true]);
    expect($enabled->effectiveBlockedWords())->toContain('fuck');

    $enabled->delete();

    $disabled = seedChatSettings(['profanity_filter_enabled' => false]);
    expect($disabled->effectiveBlockedWords())->not->toContain('fuck');
});

test('settings changes are persisted', function () {
    $admin = createChatSettingsUserWithRole('admin');
    seedChatSettings();

    $this->actingAs($admin)->put(route('admin.chat-settings.update'), [
        'blocked_words' => ['test', 'word'],
        'regex_filters' => [['pattern' => '/foo/']],
        'filter_action' => 'flag',
        'allow_urls' => false,
        'spam_repeat_threshold' => 10,
        'spam_window_seconds' => 300,
        'rate_limit_messages' => 50,
        'rate_limit_window_seconds' => 120,
        'slow_mode_enabled' => true,
        'slow_mode_cooldown_seconds' => 15,
        'slow_mode_auto_enabled' => true,
        'slow_mode_auto_threshold' => 100,
        'profanity_filter_enabled' => true,
    ]);

    $settings = ChatSetting::first();
    expect($settings->blocked_words)->toBe(['test', 'word']);
    expect($settings->regex_filters)->toBe([['pattern' => '/foo/']]);
    expect($settings->filter_action)->toBe('flag');
    expect($settings->allow_urls)->toBeFalse();
    expect($settings->spam_repeat_threshold)->toBe(10);
    expect($settings->spam_window_seconds)->toBe(300);
    expect($settings->rate_limit_messages)->toBe(50);
    expect($settings->rate_limit_window_seconds)->toBe(120);
    expect($settings->slow_mode_enabled)->toBeTrue();
    expect($settings->slow_mode_cooldown_seconds)->toBe(15);
    expect($settings->slow_mode_auto_enabled)->toBeTrue();
    expect($settings->slow_mode_auto_threshold)->toBe(100);
});
