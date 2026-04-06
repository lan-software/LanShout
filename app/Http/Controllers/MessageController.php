<?php

namespace App\Http\Controllers;

use App\Models\ChatPresence;
use App\Models\ChatSetting;
use App\Models\Message;
use App\Services\ContentModeration;
use App\Services\SlowModeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class MessageController extends Controller
{
    public function index(Request $request): JsonResource
    {
        $messages = Message::with('user:id,name,chat_color')
            ->orderBy('created_at', 'desc')
            ->paginate(perPage: (int) $request->integer('per_page', 20));

        return JsonResource::collection($messages);
    }

    public function store(
        Request $request,
        ContentModeration $moderation,
        SlowModeService $slowMode,
    ): JsonResource|JsonResponse {
        $validated = $request->validate([
            'body' => ['required', 'string', 'min:1', 'max:500'],
        ]);

        $user = $request->user();
        $settings = ChatSetting::current();

        // 1. Mute check
        $activeMute = $user->activeMute();
        if ($activeMute) {
            return response()->json([
                'message' => 'You are muted.',
                'mute' => [
                    'reason' => $activeMute->reason,
                    'expires_at' => $activeMute->expires_at?->toIso8601String(),
                ],
            ], 403);
        }

        // 2. Rate limit
        $rateLimitKey = 'chat-rate:'.$user->id;
        if (RateLimiter::tooManyAttempts($rateLimitKey, $settings->rate_limit_messages)) {
            return response()->json([
                'message' => 'Rate limit exceeded. Please wait.',
            ], 429);
        }

        // 3. Slow mode
        $slowModeResult = $slowMode->canUserSend($user->id);
        if ($slowModeResult !== true) {
            return response()->json([
                'message' => 'Slow mode active. Please wait.',
                'retry_after' => $slowModeResult,
            ], 429);
        }

        // 4. Spam check
        $sanitized = $moderation->sanitize($validated['body']);
        if ($moderation->checkSpam($user->id, $sanitized)) {
            return response()->json([
                'message' => 'Duplicate message detected. Please write something different.',
            ], 422);
        }

        // 5. Content moderation
        $result = $moderation->moderate($validated['body']);

        if ($result->action === 'blocked') {
            return response()->json([
                'message' => $result->reason ?? 'Message blocked by content filter.',
            ], 422);
        }

        // 6. Create message
        $messageData = [
            'user_id' => Auth::id(),
            'body' => $result->body,
        ];

        if ($result->action === 'flagged') {
            $messageData['flagged'] = true;
            $messageData['flag_reason'] = $result->reason;
        }

        $message = Message::create($messageData);
        $message->load('user:id,name,chat_color');

        // 7. Record rate limit hit and slow mode timestamp
        RateLimiter::hit($rateLimitKey, $settings->rate_limit_window_seconds);
        $slowMode->recordMessage($user->id);

        // 8. Update presence
        ChatPresence::updateOrCreate(
            ['user_id' => $user->id],
            ['last_seen_at' => now()],
        );

        return new JsonResource($message);
    }

    public function page(Request $request): InertiaResponse
    {
        $user = $request->user();
        $slowMode = app(SlowModeService::class);
        $settings = ChatSetting::current();

        $activeMute = $user->activeMute();

        return Inertia::render('Chat', [
            'slowModeActive' => $slowMode->isSlowModeActive(),
            'slowModeCooldown' => $settings->slow_mode_cooldown_seconds,
            'userMuted' => $activeMute !== null,
            'muteDetails' => $activeMute ? [
                'reason' => $activeMute->reason,
                'expires_at' => $activeMute->expires_at?->toIso8601String(),
            ] : null,
            'lancoreBaseUrl' => config('lancore.base_url'),
        ]);
    }

    public function heartbeat(Request $request): JsonResponse
    {
        ChatPresence::updateOrCreate(
            ['user_id' => $request->user()->id],
            ['last_seen_at' => now()],
        );

        $slowMode = app(SlowModeService::class);

        return response()->json([
            'slowModeActive' => $slowMode->isSlowModeActive(),
        ]);
    }

    public function activeUsers(): JsonResource
    {
        $users = ChatPresence::with('user:id,name,chat_color,lancore_user_id')
            ->where('last_seen_at', '>=', now()->subMinutes(5))
            ->get()
            ->map(function (ChatPresence $presence) {
                $user = $presence->user;

                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'chat_color' => $user->chat_color,
                    'lancore_user_id' => $user->lancore_user_id,
                    'roles' => $user->roles->pluck('name')->toArray(),
                ];
            });

        return JsonResource::collection($users);
    }
}
