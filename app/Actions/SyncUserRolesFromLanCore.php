<?php

namespace App\Actions;

use App\Models\Role;
use App\Models\User;

class SyncUserRolesFromLanCore
{
    /**
     * @param  array<int, string>  $roles
     */
    public function handle(User $user, array $roles): void
    {
        $mappedRoles = collect($roles)
            ->map(fn (string $role) => match ($role) {
                'superadmin' => 'super_admin',
                'admin', 'moderator', 'user' => $role,
                default => null,
            })
            ->filter()
            ->unique()
            ->values();

        $roleIds = $mappedRoles
            ->map(fn (string $role) => Role::query()->firstOrCreate(
                ['name' => $role],
                ['display_name' => str($role)->replace('_', ' ')->title()->toString()],
            )->id)
            ->all();

        $user->roles()->sync($roleIds);
    }
}