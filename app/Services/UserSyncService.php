<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserSyncService
{
    /**
     * @param  array{id:int, username:string, email:?string, locale:?string}  $lanCoreUser
     */
    public function resolveFromLanCore(array $lanCoreUser): User
    {
        $user = User::query()->where('lancore_user_id', $lanCoreUser['id'])->first();

        if ($user === null && $lanCoreUser['email'] !== null) {
            $user = User::query()->where('email', $lanCoreUser['email'])->first();
        }

        $user ??= new User;

        $user->forceFill([
            'name' => $lanCoreUser['username'],
            'email' => $lanCoreUser['email'] ?? $user->email ?? 'lancore-user-'.$lanCoreUser['id'].'@users.lancore.local',
            'email_verified_at' => now(),
            'locale' => $lanCoreUser['locale'] ?? $user->locale,
            'lancore_user_id' => $lanCoreUser['id'],
            'password' => $user->exists ? $user->getAuthPassword() : Hash::make(Str::random(40)),
        ])->save();

        return $user;
    }
}