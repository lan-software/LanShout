<?php

use App\Models\Message;

/*
|--------------------------------------------------------------------------
| Message Model Unit Tests
|--------------------------------------------------------------------------
*/

test('message has correct fillable attributes', function () {
    $message = new Message;

    expect($message->getFillable())->toBe(['user_id', 'body', 'flagged', 'flag_reason']);
});

test('message uses soft deletes trait', function () {
    $message = new Message;

    expect(method_exists($message, 'trashed'))->toBeTrue();
    expect(method_exists($message, 'restore'))->toBeTrue();
    expect(method_exists($message, 'forceDelete'))->toBeTrue();
});

test('message has user relationship method', function () {
    $message = new Message;

    expect(method_exists($message, 'user'))->toBeTrue();
});

test('message table name is messages', function () {
    $message = new Message;

    expect($message->getTable())->toBe('messages');
});
