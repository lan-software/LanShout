<?php

use App\Models\Message;
use App\Models\User;

/*
|--------------------------------------------------------------------------
| Factory Tests
|--------------------------------------------------------------------------
*/

test('user factory creates valid user', function () {
    $user = User::factory()->create();

    expect($user->name)->toBeString();
    expect($user->email)->toBeString();
    expect($user->email_verified_at)->not->toBeNull();
    expect($user->remember_token)->toBeString();
});

test('user factory unverified state sets email_verified_at to null', function () {
    $user = User::factory()->unverified()->create();

    expect($user->email_verified_at)->toBeNull();
});

test('user factory hashes password automatically', function () {
    $user = User::factory()->create();

    expect(password_verify('password', $user->password))->toBeTrue();
});

test('message factory creates valid message', function () {
    $message = Message::factory()->create();

    expect($message->body)->toBeString();
    expect($message->user_id)->not->toBeNull();
    expect($message->user)->toBeInstanceOf(User::class);
});

test('message factory creates user automatically', function () {
    $message = Message::factory()->create();

    expect(User::find($message->user_id))->not->toBeNull();
});

test('message factory respects overrides', function () {
    $user = User::factory()->create();
    $message = Message::factory()->create([
        'user_id' => $user->id,
        'body' => 'Custom body',
    ]);

    expect($message->user_id)->toBe($user->id);
    expect($message->body)->toBe('Custom body');
});

test('user factory generates unique emails', function () {
    $users = User::factory()->count(50)->create();
    $emails = $users->pluck('email')->toArray();

    expect(count(array_unique($emails)))->toBe(50);
});

test('message factory count creates correct number', function () {
    $messages = Message::factory()->count(10)->create();

    expect($messages)->toHaveCount(10);
    expect(Message::count())->toBe(10);
});
