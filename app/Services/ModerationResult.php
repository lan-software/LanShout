<?php

namespace App\Services;

class ModerationResult
{
    public function __construct(
        public readonly string $action,
        public readonly string $body,
        public readonly ?string $reason = null,
    ) {}

    public static function passed(string $body): self
    {
        return new self('passed', $body);
    }

    public static function blocked(string $reason): self
    {
        return new self('blocked', '', $reason);
    }

    public static function censored(string $body): self
    {
        return new self('censored', $body);
    }

    public static function flagged(string $body, string $reason): self
    {
        return new self('flagged', $body, $reason);
    }
}
