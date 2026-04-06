<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MuteUserRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'reason' => ['nullable', 'string', 'max:500'],
            'duration' => ['nullable', 'integer', 'min:1', 'max:525600'], // max 1 year in minutes
        ];
    }
}
