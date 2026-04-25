<?php

use App\Models\Role;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users without role get 403', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertStatus(403);
});

test('admin users can visit the dashboard', function () {
    $role = Role::create(['name' => 'admin', 'display_name' => 'Admin']);
    $user = User::factory()->create();
    $user->roles()->attach($role);
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertStatus(200);
});
