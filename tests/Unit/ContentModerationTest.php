<?php

use App\Models\ChatSetting;
use App\Services\ContentModeration;

beforeEach(function () {
    $this->moderation = new ContentModeration();
});

test('trims leading and trailing whitespace', function () {
    expect($this->moderation->sanitize('  hello  '))->toBe('hello');
});

test('collapses multiple whitespace characters into one', function () {
    expect($this->moderation->sanitize('hello    world'))->toBe('hello world');
});

test('collapses newlines and tabs into spaces', function () {
    expect($this->moderation->sanitize("hello\n\nworld\ttoo"))->toBe('hello world too');
});

test('strips HTML tags', function () {
    expect($this->moderation->sanitize('<b>bold</b> and <script>alert("xss")</script>'))->toBe('bold and alert("xss")');
});

test('handles nested HTML tags', function () {
    expect($this->moderation->sanitize('<div><p>nested</p></div>'))->toBe('nested');
});

test('returns empty string for whitespace-only input', function () {
    expect($this->moderation->sanitize('   '))->toBe('');
});

test('preserves plain text unchanged', function () {
    expect($this->moderation->sanitize('Hello, World!'))->toBe('Hello, World!');
});

test('handles unicode characters', function () {
    expect($this->moderation->sanitize('  Héllo wörld  '))->toBe('Héllo wörld');
});

test('handles empty string', function () {
    expect($this->moderation->sanitize(''))->toBe('');
});

test('strips self-closing HTML tags', function () {
    expect($this->moderation->sanitize('before<br/>after'))->toBe('beforeafter');
});

// --- ContentModeration@moderate ---

test('moderate passes clean message', function () {
    $settings = new ChatSetting([
        'blocked_words' => [],
        'regex_filters' => [],
        'filter_action' => 'block',
        'allow_urls' => true,
    ]);
    $moderation = new ContentModeration($settings);
    $result = $moderation->moderate('Hello world');

    expect($result->action)->toBe('passed');
    expect($result->body)->toBe('Hello world');
});

test('moderate blocks message with blocked word when filter_action is block', function () {
    $settings = new ChatSetting([
        'blocked_words' => ['badword'],
        'regex_filters' => [],
        'filter_action' => 'block',
        'allow_urls' => true,
    ]);
    $moderation = new ContentModeration($settings);
    $result = $moderation->moderate('This has a badword in it');

    expect($result->action)->toBe('blocked');
    expect($result->body)->toBe('');
    expect($result->reason)->toBe('Message contains prohibited content');
});

test('moderate censors blocked word with asterisks when filter_action is censor', function () {
    $settings = new ChatSetting([
        'blocked_words' => ['badword'],
        'regex_filters' => [],
        'filter_action' => 'censor',
        'allow_urls' => true,
    ]);
    $moderation = new ContentModeration($settings);
    $result = $moderation->moderate('This has a badword in it');

    expect($result->action)->toBe('censored');
    expect($result->body)->toContain('*******');
    expect($result->body)->not->toContain('badword');
});

test('moderate flags message when filter_action is flag', function () {
    $settings = new ChatSetting([
        'blocked_words' => ['suspicious'],
        'regex_filters' => [],
        'filter_action' => 'flag',
        'allow_urls' => true,
    ]);
    $moderation = new ContentModeration($settings);
    $result = $moderation->moderate('This is suspicious content');

    expect($result->action)->toBe('flagged');
    expect($result->body)->toContain('suspicious');
    expect($result->reason)->toBe('Content filter match');
});

test('moderate handles regex filter match', function () {
    $settings = new ChatSetting([
        'blocked_words' => [],
        'regex_filters' => [['pattern' => '/\d{3}-\d{3}-\d{4}/']],
        'filter_action' => 'block',
        'allow_urls' => true,
    ]);
    $moderation = new ContentModeration($settings);
    $result = $moderation->moderate('Call me at 555-123-4567');

    expect($result->action)->toBe('blocked');
    expect($result->reason)->toBe('Message contains prohibited content');
});

test('moderate skips invalid regex patterns gracefully', function () {
    $settings = new ChatSetting([
        'blocked_words' => [],
        'regex_filters' => [['pattern' => '/invalid[regex']],
        'filter_action' => 'block',
        'allow_urls' => true,
    ]);
    $moderation = new ContentModeration($settings);
    $result = $moderation->moderate('Normal message');

    expect($result->action)->toBe('passed');
    expect($result->body)->toBe('Normal message');
});

test('moderate blocks URLs when allow_urls is false', function () {
    $settings = new ChatSetting([
        'blocked_words' => [],
        'regex_filters' => [],
        'filter_action' => 'block',
        'allow_urls' => false,
    ]);
    $moderation = new ContentModeration($settings);
    $result = $moderation->moderate('Visit https://example.com now');

    expect($result->action)->toBe('blocked');
    expect($result->reason)->toBe('URLs are not allowed');
});

test('moderate allows URLs when allow_urls is true', function () {
    $settings = new ChatSetting([
        'blocked_words' => [],
        'regex_filters' => [],
        'filter_action' => 'block',
        'allow_urls' => true,
    ]);
    $moderation = new ContentModeration($settings);
    $result = $moderation->moderate('Visit https://example.com now');

    expect($result->action)->toBe('passed');
    expect($result->body)->toContain('https://example.com');
});

test('moderate handles empty blocked words list', function () {
    $settings = new ChatSetting([
        'blocked_words' => [],
        'regex_filters' => [],
        'filter_action' => 'block',
        'allow_urls' => true,
    ]);
    $moderation = new ContentModeration($settings);
    $result = $moderation->moderate('Any message is fine');

    expect($result->action)->toBe('passed');
    expect($result->body)->toBe('Any message is fine');
});
