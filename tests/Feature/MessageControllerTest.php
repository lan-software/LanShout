<?php

use App\Models\ChatPresence;
use App\Models\ChatSetting;
use App\Models\Message;
use App\Models\User;
use App\Models\UserMute;

// --- MessageController@index ---

test('messages index returns paginated JSON', function () {
    Message::factory()->count(5)->create();

    $response = $this->getJson(route('messages.index'));

    $response->assertOk();
    $response->assertJsonStructure([
        'data' => [['id', 'body', 'created_at', 'user']],
        'meta' => ['current_page', 'last_page', 'per_page', 'total'],
    ]);
});

test('messages index respects per_page parameter', function () {
    Message::factory()->count(10)->create();

    $response = $this->getJson(route('messages.index', ['per_page' => 3]));

    $response->assertOk();
    $response->assertJsonCount(3, 'data');
    $response->assertJsonPath('meta.per_page', 3);
});

test('messages index returns newest first', function () {
    $old = Message::factory()->create(['created_at' => now()->subHour()]);
    $new = Message::factory()->create(['created_at' => now()]);

    $response = $this->getJson(route('messages.index'));

    $response->assertOk();
    $ids = collect($response->json('data'))->pluck('id')->toArray();
    expect($ids[0])->toBe($new->id);
    expect($ids[1])->toBe($old->id);
});

test('messages index includes user data', function () {
    $user = User::factory()->create(['name' => 'TestUser', 'chat_color' => '#ff0000']);
    Message::factory()->create(['user_id' => $user->id]);

    $response = $this->getJson(route('messages.index'));

    $response->assertOk();
    $response->assertJsonPath('data.0.user.name', 'TestUser');
    $response->assertJsonPath('data.0.user.chat_color', '#ff0000');
});

test('messages index returns empty data when no messages', function () {
    $response = $this->getJson(route('messages.index'));

    $response->assertOk();
    $response->assertJsonCount(0, 'data');
});

test('messages index supports pagination', function () {
    Message::factory()->count(25)->create();

    $response = $this->getJson(route('messages.index', ['page' => 2, 'per_page' => 10]));

    $response->assertOk();
    $response->assertJsonPath('meta.current_page', 2);
    $response->assertJsonCount(10, 'data');
});

// --- MessageController@store ---

test('authenticated user can post a message', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson(route('messages.store'), [
        'body' => 'Hello LAN!',
    ]);

    $response->assertCreated();
    $response->assertJsonPath('data.body', 'Hello LAN!');
    $response->assertJsonPath('data.user.id', $user->id);

    $this->assertDatabaseHas('messages', [
        'user_id' => $user->id,
        'body' => 'Hello LAN!',
    ]);
});

test('guest cannot post a message', function () {
    $response = $this->postJson(route('messages.store'), [
        'body' => 'Hello!',
    ]);

    $response->assertUnauthorized();
});

test('message body is required', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson(route('messages.store'), [
        'body' => '',
    ]);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors('body');
});

test('message body must not exceed 500 characters', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson(route('messages.store'), [
        'body' => str_repeat('a', 501),
    ]);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors('body');
});

test('message body at max length is accepted', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson(route('messages.store'), [
        'body' => str_repeat('a', 500),
    ]);

    $response->assertCreated();
});

test('message body is sanitized', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson(route('messages.store'), [
        'body' => '  <b>bold</b>  text  ',
    ]);

    $response->assertCreated();
    $response->assertJsonPath('data.body', 'bold text');
});

test('store returns user data with the message', function () {
    $user = User::factory()->create(['name' => 'Chatter', 'chat_color' => '#00ff00']);

    $response = $this->actingAs($user)->postJson(route('messages.store'), [
        'body' => 'test',
    ]);

    $response->assertCreated();
    $response->assertJsonPath('data.user.name', 'Chatter');
    $response->assertJsonPath('data.user.chat_color', '#00ff00');
});

// --- MessageController@page ---

test('authenticated verified user can visit chat page', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('chat'));

    $response->assertOk();
});

test('guest is redirected from chat page', function () {
    $response = $this->get(route('chat'));

    $response->assertRedirect(route('login'));
});

test('unverified user is redirected from chat page', function () {
    $user = User::factory()->unverified()->create();

    $response = $this->actingAs($user)->get(route('chat'));

    $response->assertRedirect(route('verification.notice'));
});

// --- Mute enforcement ---

test('muted user cannot post a message', function () {
    $user = User::factory()->create();
    ChatSetting::create([
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
    ]);

    UserMute::create([
        'user_id' => $user->id,
        'muted_by' => $user->id,
        'reason' => 'Spamming',
        'expires_at' => now()->addHour(),
    ]);

    $response = $this->actingAs($user)->postJson(route('messages.store'), [
        'body' => 'I am muted',
    ]);

    $response->assertForbidden();
    $response->assertJsonPath('message', 'You are muted.');
    $response->assertJsonPath('mute.reason', 'Spamming');
});

test('expired mute allows posting', function () {
    $user = User::factory()->create();
    ChatSetting::create([
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
    ]);

    UserMute::create([
        'user_id' => $user->id,
        'muted_by' => $user->id,
        'reason' => 'Old mute',
        'expires_at' => now()->subMinute(),
    ]);

    $response = $this->actingAs($user)->postJson(route('messages.store'), [
        'body' => 'I am no longer muted',
    ]);

    $response->assertCreated();
});

// --- Heartbeat and presence ---

test('heartbeat updates chat presence', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson(route('chat.heartbeat'));

    $response->assertOk();
    $response->assertJsonStructure(['slowModeActive']);

    $this->assertDatabaseHas('chat_presences', [
        'user_id' => $user->id,
    ]);
});

test('active users returns users with recent presence', function () {
    $user1 = User::factory()->create(['name' => 'ActiveUser']);
    $user2 = User::factory()->create(['name' => 'StaleUser']);

    ChatPresence::create([
        'user_id' => $user1->id,
        'last_seen_at' => now(),
    ]);

    ChatPresence::create([
        'user_id' => $user2->id,
        'last_seen_at' => now()->subMinutes(10),
    ]);

    $response = $this->actingAs($user1)->getJson(route('chat.active-users'));

    $response->assertOk();

    $names = collect($response->json('data'))->pluck('name')->toArray();
    expect($names)->toContain('ActiveUser');
    expect($names)->not->toContain('StaleUser');
});

// --- Spam check (DB-dependent) ---

test('checkSpam returns true when threshold exceeded', function () {
    $user = User::factory()->create();
    ChatSetting::create([
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
    ]);

    // Create 3 identical messages within the window
    Message::factory()->count(3)->create([
        'user_id' => $user->id,
        'body' => 'spam message',
        'created_at' => now(),
    ]);

    $moderation = new \App\Services\ContentModeration();
    expect($moderation->checkSpam($user->id, 'spam message'))->toBeTrue();
});

test('checkSpam returns false below threshold', function () {
    $user = User::factory()->create();
    ChatSetting::create([
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
    ]);

    // Create only 2 identical messages (below threshold of 3)
    Message::factory()->count(2)->create([
        'user_id' => $user->id,
        'body' => 'repeated message',
        'created_at' => now(),
    ]);

    $moderation = new \App\Services\ContentModeration();
    expect($moderation->checkSpam($user->id, 'repeated message'))->toBeFalse();
});
