<?php

namespace App\Services;

use App\Models\ChatSetting;
use App\Models\Message;

class ContentModeration
{
    public function __construct(
        private ?ChatSetting $settings = null,
    ) {}

    public function moderate(string $body): ModerationResult
    {
        $clean = $this->sanitize($body);
        $settings = $this->settings ?? ChatSetting::current();

        // Check URL filtering
        if (! $settings->allow_urls && $this->containsUrl($clean)) {
            return match ($settings->filter_action) {
                'block' => ModerationResult::blocked('URLs are not allowed'),
                'flag' => ModerationResult::flagged($clean, 'Message contains a URL'),
                default => ModerationResult::censored($this->censorUrls($clean)),
            };
        }

        // Check blocked words (custom + preset categories)
        $effectiveWords = $settings->effectiveBlockedWords();
        $matchedWords = $this->findBlockedWords($clean, $effectiveWords);

        // Check regex filters
        $matchedPatterns = $this->findRegexMatches($clean, $settings->regex_filters);

        if (count($matchedWords) > 0 || count($matchedPatterns) > 0) {
            return match ($settings->filter_action) {
                'block' => ModerationResult::blocked('Message contains prohibited content'),
                'flag' => ModerationResult::flagged($clean, 'Content filter match'),
                default => ModerationResult::censored(
                    $this->censorText($clean, $matchedWords, $matchedPatterns),
                ),
            };
        }

        return ModerationResult::passed($clean);
    }

    public function checkSpam(int $userId, string $body): bool
    {
        $settings = $this->settings ?? ChatSetting::current();

        $recentSameMessages = Message::where('user_id', $userId)
            ->where('body', $body)
            ->where('created_at', '>=', now()->subSeconds($settings->spam_window_seconds))
            ->count();

        return $recentSameMessages >= $settings->spam_repeat_threshold;
    }

    public function sanitize(string $body): string
    {
        $clean = trim($body);
        $clean = preg_replace('/\s+/u', ' ', $clean ?? '');
        $clean = strip_tags($clean);

        return (string) $clean;
    }

    private function findBlockedWords(string $body, array $words): array
    {
        if (empty($words)) {
            return [];
        }

        $matched = [];
        $lowerBody = mb_strtolower($body);

        foreach ($words as $word) {
            $word = trim($word);
            if ($word === '') {
                continue;
            }
            if (mb_strpos($lowerBody, mb_strtolower($word)) !== false) {
                $matched[] = $word;
            }
        }

        return $matched;
    }

    private function findRegexMatches(string $body, array $filters): array
    {
        if (empty($filters)) {
            return [];
        }

        $matched = [];

        foreach ($filters as $filter) {
            $pattern = $filter['pattern'] ?? '';
            if ($pattern === '') {
                continue;
            }

            // Validate regex before using it
            if (@preg_match($pattern, '') === false) {
                continue;
            }

            if (preg_match($pattern, $body)) {
                $matched[] = $pattern;
            }
        }

        return $matched;
    }

    private function containsUrl(string $body): bool
    {
        return (bool) preg_match('/https?:\/\/\S+/i', $body);
    }

    private function censorUrls(string $body): string
    {
        return preg_replace('/https?:\/\/\S+/i', '***', $body) ?? $body;
    }

    private function censorText(string $body, array $words, array $patterns): string
    {
        $result = $body;

        foreach ($words as $word) {
            $result = preg_replace(
                '/'.preg_quote($word, '/').'/iu',
                str_repeat('*', mb_strlen($word)),
                $result,
            ) ?? $result;
        }

        foreach ($patterns as $pattern) {
            $result = preg_replace_callback($pattern, function ($match) {
                return str_repeat('*', mb_strlen($match[0]));
            }, $result) ?? $result;
        }

        return $result;
    }
}
