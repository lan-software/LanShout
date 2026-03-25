<?php

use App\Models\Permission;
use App\Models\Role;
use Database\Seeders\RolePermissionSeeder;

/*
|--------------------------------------------------------------------------
| RolePermissionSeeder Tests
|--------------------------------------------------------------------------
*/

test('seeder creates all expected roles', function () {
    $this->seed(RolePermissionSeeder::class);

    expect(Role::where('name', 'super_admin')->exists())->toBeTrue();
    expect(Role::where('name', 'admin')->exists())->toBeTrue();
    expect(Role::where('name', 'moderator')->exists())->toBeTrue();
    expect(Role::where('name', 'user')->exists())->toBeTrue();
    expect(Role::count())->toBe(4);
});

test('seeder creates all expected permissions', function () {
    $this->seed(RolePermissionSeeder::class);

    $expectedPermissions = [
        'view_chat',
        'send_chat_message',
        'delete_chat_message',
        'edit_user',
        'delete_user',
        'edit_chat_configuration',
        'edit_system_configuration',
    ];

    foreach ($expectedPermissions as $perm) {
        expect(Permission::where('name', $perm)->exists())->toBeTrue("Permission '{$perm}' should exist");
    }

    expect(Permission::count())->toBe(7);
});

test('super admin has all permissions', function () {
    $this->seed(RolePermissionSeeder::class);

    $superAdmin = Role::where('name', 'super_admin')->first();

    expect($superAdmin->permissions)->toHaveCount(7);
});

test('admin has all permissions except system configuration', function () {
    $this->seed(RolePermissionSeeder::class);

    $admin = Role::where('name', 'admin')->first();
    $permissionNames = $admin->permissions->pluck('name')->toArray();

    expect($permissionNames)->not->toContain('edit_system_configuration');
    expect($admin->permissions)->toHaveCount(6);
});

test('moderator has correct permissions', function () {
    $this->seed(RolePermissionSeeder::class);

    $moderator = Role::where('name', 'moderator')->first();
    $permissionNames = $moderator->permissions->pluck('name')->toArray();

    expect($permissionNames)->toContain('view_chat');
    expect($permissionNames)->toContain('send_chat_message');
    expect($permissionNames)->toContain('delete_chat_message');
    expect($permissionNames)->toContain('edit_user');
    expect($moderator->permissions)->toHaveCount(4);
});

test('user role has only view and send chat permissions', function () {
    $this->seed(RolePermissionSeeder::class);

    $userRole = Role::where('name', 'user')->first();
    $permissionNames = $userRole->permissions->pluck('name')->toArray();

    expect($permissionNames)->toContain('view_chat');
    expect($permissionNames)->toContain('send_chat_message');
    expect($userRole->permissions)->toHaveCount(2);
});

test('seeder is idempotent', function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(RolePermissionSeeder::class);

    expect(Role::count())->toBe(4);
    expect(Permission::count())->toBe(7);
});
