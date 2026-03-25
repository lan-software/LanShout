<?php

use App\Models\Message;
use App\Models\User;

test('message belongs to a user', function () {
    $message = Message::factory()->create();

    expect($message->user)->toBeInstanceOf(User::class);
});

test('message can be created with factory', function () {
    $message = Message::factory()->create(['body' => 'Test message']);

    expect($message->body)->toBe('Test message');
    expect($message->user_id)->not->toBeNull();
});

test('message uses soft deletes', function () {
    $message = Message::factory()->create();
    $messageId = $message->id;

    $message->delete();

    expect(Message::find($messageId))->toBeNull();
    expect(Message::withTrashed()->find($messageId))->not->toBeNull();
    expect(Message::withTrashed()->find($messageId)->trashed())->toBeTrue();
});

test('message fillable attributes are set correctly', function () {
    $user = User::factory()->create();
    $message = Message::create([
        'user_id' => $user->id,
        'body' => 'Hello world',
    ]);

    expect($message->user_id)->toBe($user->id);
    expect($message->body)->toBe('Hello world');
});
