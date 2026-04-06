<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\MuteUserRequest;
use App\Models\User;
use App\Models\UserMute;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;

class UserMuteController extends Controller
{
    public function store(MuteUserRequest $request, User $user): JsonResource
    {
        abort_unless(
            auth()->check() && auth()->user()->hasAnyRole(['super_admin', 'admin', 'moderator']),
            403,
        );

        abort_if($user->id === auth()->id(), 422, 'You cannot mute yourself.');

        // Moderators cannot mute admins/super_admins
        if (
            ! auth()->user()->hasAnyRole(['super_admin', 'admin'])
            && $user->hasAnyRole(['super_admin', 'admin'])
        ) {
            abort(403, 'You cannot mute an administrator.');
        }

        $mute = UserMute::create([
            'user_id' => $user->id,
            'muted_by' => auth()->id(),
            'reason' => $request->validated('reason'),
            'expires_at' => $request->validated('duration')
                ? now()->addMinutes($request->validated('duration'))
                : null,
        ]);

        return new JsonResource($mute);
    }

    public function destroy(UserMute $mute): JsonResponse
    {
        abort_unless(
            auth()->check() && auth()->user()->hasAnyRole(['super_admin', 'admin', 'moderator']),
            403,
        );

        $mute->delete();

        return response()->json(['message' => 'Mute removed']);
    }
}
