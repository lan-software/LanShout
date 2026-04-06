<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateChatSettingsRequest;
use App\Models\ChatSetting;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class ChatSettingsController extends Controller
{
    public function index(): InertiaResponse
    {
        abort_unless(
            auth()->check() && auth()->user()->hasAnyRole(['super_admin', 'admin', 'moderator']),
            403,
        );

        $presets = collect(config('chat-filters', []))->map(function (array $preset, string $key) {
            return [
                'key' => $key,
                'label' => $preset['label'] ?? $key,
                'description' => $preset['description'] ?? '',
                'wordCount' => count($preset['words'] ?? []),
                'alwaysActiveInNsfw' => $preset['always_active_in_nsfw'] ?? false,
            ];
        })->values()->all();

        return Inertia::render('admin/ChatSettings', [
            'settings' => ChatSetting::current(),
            'canEdit' => auth()->user()->hasAnyRole(['super_admin', 'admin']),
            'filterPresets' => $presets,
        ]);
    }

    public function update(UpdateChatSettingsRequest $request): RedirectResponse
    {
        abort_unless(
            auth()->check() && auth()->user()->hasAnyRole(['super_admin', 'admin']),
            403,
        );

        $settings = ChatSetting::current();
        $settings->update($request->validated());
        $settings->clearCache();

        return back();
    }
}
