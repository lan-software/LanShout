<?php

use App\Models\Role;
use App\Models\User;

test('promotes user to admin by email', function () {
    $role = Role::create(['name' => 'admin', 'display_name' => 'Admin']);
    $user = User::factory()->create(['email' => 'test@example.com']);

    $this->artisan('user:promote', ['email' => 'test@example.com'])
        ->expectsOutput("User [{$user->name}] has been promoted to admin.")
        ->assertExitCode(0);

    expect($user->fresh()->hasRole('admin'))->toBeTrue();
});

test('fails when user does not exist', function () {
    Role::create(['name' => 'admin', 'display_name' => 'Admin']);

    $this->artisan('user:promote', ['email' => 'nonexistent@example.com'])
        ->expectsOutput('User with email [nonexistent@example.com] not found.')
        ->assertExitCode(1);
});

test('fails when admin role does not exist', function () {
    User::factory()->create(['email' => 'test@example.com']);

    $this->artisan('user:promote', ['email' => 'test@example.com'])
        ->expectsOutput('Admin role does not exist. Please run database seeders first.')
        ->assertExitCode(1);
});

test('warns when user already has admin role', function () {
    $role = Role::create(['name' => 'admin', 'display_name' => 'Admin']);
    $user = User::factory()->create(['email' => 'test@example.com']);
    $user->roles()->attach($role);

    $this->artisan('user:promote', ['email' => 'test@example.com'])
        ->expectsOutput("User [{$user->name}] already has the admin role.")
        ->assertExitCode(0);
});
