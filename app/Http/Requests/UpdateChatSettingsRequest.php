<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateChatSettingsRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'allow_urls' => $this->boolean('allow_urls'),
            'slow_mode_enabled' => $this->boolean('slow_mode_enabled'),
            'slow_mode_auto_enabled' => $this->boolean('slow_mode_auto_enabled'),
            'profanity_filter_enabled' => $this->boolean('profanity_filter_enabled'),
        ]);
    }

    public function rules(): array
    {
        return [
            'blocked_words' => ['present', 'array'],
            'blocked_words.*' => ['string', 'max:255'],
            'regex_filters' => ['present', 'array'],
            'regex_filters.*.pattern' => ['required', 'string', 'max:500'],
            'filter_action' => ['required', Rule::in(['block', 'censor', 'flag'])],
            'allow_urls' => ['required', 'boolean'],
            'spam_repeat_threshold' => ['required', 'integer', 'min:1', 'max:100'],
            'spam_window_seconds' => ['required', 'integer', 'min:10', 'max:600'],
            'rate_limit_messages' => ['required', 'integer', 'min:1', 'max:100'],
            'rate_limit_window_seconds' => ['required', 'integer', 'min:10', 'max:600'],
            'slow_mode_enabled' => ['required', 'boolean'],
            'slow_mode_cooldown_seconds' => ['required', 'integer', 'min:1', 'max:300'],
            'slow_mode_auto_enabled' => ['required', 'boolean'],
            'slow_mode_auto_threshold' => ['required', 'integer', 'min:5', 'max:1000'],
            'profanity_filter_enabled' => ['required', 'boolean'],
        ];
    }
}
