<?php

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;

test('user has roles relationship', function () {
    $user = User::factory()->create();
    $role = Role::create(['name' => 'admin', 'display_name' => 'Admin']);
    $user->roles()->attach($role);

    expect($user->roles)->toHaveCount(1);
    expect($user->roles->first()->name)->toBe('admin');
});

test('hasRole returns true when user has the role', function () {
    $user = User::factory()->create();
    $role = Role::create(['name' => 'moderator', 'display_name' => 'Moderator']);
    $user->roles()->attach($role);

    expect($user->hasRole('moderator'))->toBeTrue();
});

test('hasRole returns false when user does not have the role', function () {
    $user = User::factory()->create();

    expect($user->hasRole('admin'))->toBeFalse();
});

test('hasAnyRole returns true when user has one of the roles', function () {
    $user = User::factory()->create();
    $role = Role::create(['name' => 'admin', 'display_name' => 'Admin']);
    $user->roles()->attach($role);

    expect($user->hasAnyRole(['admin', 'super_admin']))->toBeTrue();
});

test('hasAnyRole returns false when user has none of the roles', function () {
    $user = User::factory()->create();

    expect($user->hasAnyRole(['admin', 'super_admin']))->toBeFalse();
});

test('user can have multiple roles', function () {
    $user = User::factory()->create();
    $admin = Role::create(['name' => 'admin', 'display_name' => 'Admin']);
    $mod = Role::create(['name' => 'moderator', 'display_name' => 'Moderator']);
    $user->roles()->attach([$admin->id, $mod->id]);

    expect($user->roles)->toHaveCount(2);
    expect($user->hasRole('admin'))->toBeTrue();
    expect($user->hasRole('moderator'))->toBeTrue();
});

test('role has users relationship', function () {
    $role = Role::create(['name' => 'user', 'display_name' => 'User']);
    $user = User::factory()->create();
    $role->users()->attach($user);

    expect($role->users)->toHaveCount(1);
    expect($role->users->first()->id)->toBe($user->id);
});

test('role has permissions relationship', function () {
    $role = Role::create(['name' => 'admin', 'display_name' => 'Admin']);
    $perm = Permission::create(['name' => 'edit_user', 'display_name' => 'Edit User']);
    $role->permissions()->attach($perm);

    expect($role->permissions)->toHaveCount(1);
    expect($role->permissions->first()->name)->toBe('edit_user');
});

test('permission has roles relationship', function () {
    $perm = Permission::create(['name' => 'view_chat', 'display_name' => 'View Chat']);
    $role = Role::create(['name' => 'user', 'display_name' => 'User']);
    $perm->roles()->attach($role);

    expect($perm->roles)->toHaveCount(1);
    expect($perm->roles->first()->name)->toBe('user');
});
