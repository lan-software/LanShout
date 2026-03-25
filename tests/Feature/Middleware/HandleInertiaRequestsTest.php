<?php

use App\Models\Role;
use App\Models\User;

/*
|--------------------------------------------------------------------------
| HandleInertiaRequests Middleware Tests
|--------------------------------------------------------------------------
*/

test('shares app name in inertia props', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('chat'));

    $response->assertOk();
    $page = $response->original->getData()['page'];
    $props = $page['props'];

    expect($props['name'])->toBe(config('app.name'));
});

test('shares quote in inertia props', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('chat'));

    $response->assertOk();
    $page = $response->original->getData()['page'];
    $props = $page['props'];

    expect($props['quote'])->toHaveKeys(['message', 'author']);
    expect($props['quote']['message'])->toBeString();
    expect($props['quote']['author'])->toBeString();
});

test('shares authenticated user data', function () {
    $role = Role::create(['name' => 'admin', 'display_name' => 'Admin']);
    $user = User::factory()->create(['name' => 'Test User', 'email' => 'test@example.com']);
    $user->roles()->attach($role);

    $response = $this->actingAs($user)->get(route('chat'));

    $response->assertOk();
    $page = $response->original->getData()['page'];
    $auth = $page['props']['auth'];

    expect($auth['user']['id'])->toBe($user->id);
    expect($auth['user']['name'])->toBe('Test User');
    expect($auth['user']['email'])->toBe('test@example.com');
    expect($auth['user']['roles'])->toContain('admin');
});

test('shares null user for guests', function () {
    $response = $this->get(route('home'));

    $response->assertOk();
    $page = $response->original->getData()['page'];
    $auth = $page['props']['auth'];

    expect($auth['user'])->toBeNull();
});

test('shares sidebarOpen as true by default', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('chat'));

    $page = $response->original->getData()['page'];
    expect($page['props']['sidebarOpen'])->toBeTrue();
});
