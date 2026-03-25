<?php

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
