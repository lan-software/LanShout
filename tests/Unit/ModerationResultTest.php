<?php

use App\Services\ModerationResult;

test('passed() returns action passed with body and no reason', function () {
    $result = ModerationResult::passed('Hello world');

    expect($result->action)->toBe('passed');
    expect($result->body)->toBe('Hello world');
    expect($result->reason)->toBeNull();
});

test('blocked() returns action blocked with empty body and reason', function () {
    $result = ModerationResult::blocked('Contains bad words');

    expect($result->action)->toBe('blocked');
    expect($result->body)->toBe('');
    expect($result->reason)->toBe('Contains bad words');
});

test('censored() returns action censored with body and no reason', function () {
    $result = ModerationResult::censored('Hello *** world');

    expect($result->action)->toBe('censored');
    expect($result->body)->toBe('Hello *** world');
    expect($result->reason)->toBeNull();
});

test('flagged() returns action flagged with body and reason', function () {
    $result = ModerationResult::flagged('Suspicious message', 'Matched filter');

    expect($result->action)->toBe('flagged');
    expect($result->body)->toBe('Suspicious message');
    expect($result->reason)->toBe('Matched filter');
});
