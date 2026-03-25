<?php

use App\Models\Message;
use App\Models\User;

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
