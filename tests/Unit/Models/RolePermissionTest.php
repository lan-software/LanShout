<?php

use App\Models\Permission;
use App\Models\Role;

/*
|--------------------------------------------------------------------------
| Role Model Unit Tests
|--------------------------------------------------------------------------
*/

test('role has correct fillable attributes', function () {
    $role = new Role;

    expect($role->getFillable())->toBe(['name', 'display_name', 'description']);
});

test('role has users relationship method', function () {
    $role = new Role;

    expect(method_exists($role, 'users'))->toBeTrue();
});

test('role has permissions relationship method', function () {
    $role = new Role;

    expect(method_exists($role, 'permissions'))->toBeTrue();
});

/*
|--------------------------------------------------------------------------
| Permission Model Unit Tests
|--------------------------------------------------------------------------
*/

test('permission has correct fillable attributes', function () {
    $permission = new Permission;

    expect($permission->getFillable())->toBe(['name', 'display_name', 'description']);
});

test('permission has roles relationship method', function () {
    $permission = new Permission;

    expect(method_exists($permission, 'roles'))->toBeTrue();
});
