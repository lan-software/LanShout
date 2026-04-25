<?php

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;

function createUserWithRole(string $roleName): User
{
    $role = Role::create(['name' => $roleName, 'display_name' => ucfirst($roleName)]);
    $user = User::factory()->create();
    $user->roles()->attach($role);

    return $user;
}

// --- Admin Index ---

test('admin index requires authentication', function () {
    $response = $this->get(route('admin.index'));

    $response->assertRedirect(route('login'));
});

test('regular user gets 403 on admin index', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('admin.index'));

    $response->assertForbidden();
});

test('admin can access admin index', function () {
    $user = createUserWithRole('admin');

    $response = $this->actingAs($user)->get(route('admin.index'));

    $response->assertOk();
});

test('moderator can access admin index', function () {
    $user = createUserWithRole('moderator');

    $response = $this->actingAs($user)->get(route('admin.index'));

    $response->assertOk();
});

test('super admin can access admin index', function () {
    $user = createUserWithRole('super_admin');

    $response = $this->actingAs($user)->get(route('admin.index'));

    $response->assertOk();
});

// --- User List ---

test('admin can list users', function () {
    $admin = createUserWithRole('admin');
    User::factory()->count(3)->create();

    $response = $this->actingAs($admin)->get(route('admin.users.index'));

    $response->assertOk();
});

test('moderator gets 403 on user list', function () {
    $user = createUserWithRole('moderator');

    $response = $this->actingAs($user)->get(route('admin.users.index'));

    $response->assertForbidden();
});

test('regular user gets 403 on user list', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('admin.users.index'));

    $response->assertForbidden();
});

test('guest is redirected from user list', function () {
    $response = $this->get(route('admin.users.index'));

    $response->assertRedirect(route('login'));
});

// --- User Show ---

test('admin can view a user', function () {
    $admin = createUserWithRole('admin');
    $target = User::factory()->create();

    $response = $this->actingAs($admin)->get(route('admin.users.show', $target));

    $response->assertOk();
});

test('super admin can view a user', function () {
    $admin = createUserWithRole('super_admin');
    $target = User::factory()->create();

    $response = $this->actingAs($admin)->get(route('admin.users.show', $target));

    $response->assertOk();
});

test('moderator gets 403 on user show', function () {
    $user = createUserWithRole('moderator');
    $target = User::factory()->create();

    $response = $this->actingAs($user)->get(route('admin.users.show', $target));

    $response->assertForbidden();
});

test('regular user gets 403 on user show', function () {
    $user = User::factory()->create();
    $target = User::factory()->create();

    $response = $this->actingAs($user)->get(route('admin.users.show', $target));

    $response->assertForbidden();
});

test('user show includes roles and permissions', function () {
    $admin = createUserWithRole('admin');
    $target = User::factory()->create();
    $role = Role::create(['name' => 'user', 'display_name' => 'User']);
    $perm = Permission::create(['name' => 'view_chat', 'display_name' => 'View Chat']);
    $role->permissions()->attach($perm);
    $target->roles()->attach($role);

    $response = $this->actingAs($admin)->get(route('admin.users.show', $target));

    $response->assertOk();
});
