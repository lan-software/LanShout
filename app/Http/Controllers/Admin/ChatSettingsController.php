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

        return Inertia::render('admin/ChatSettings', [
            'settings' => ChatSetting::current(),
            'canEdit' => auth()->user()->hasAnyRole(['super_admin', 'admin']),
        ]);
    }

    public function update(UpdateChatSettingsRequest $request): RedirectResponse
    {
        abort_unless(
            auth()->check() && auth()->user()->hasAnyRole(['super_admin', 'admin']),
            403,
        );

        $settings = ChatSetting::firstOrFail();
        $settings->fill($request->validated());
        $settings->save();
        $settings->clearCache();

        return back();
    }
}
