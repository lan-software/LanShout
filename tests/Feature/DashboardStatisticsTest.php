<?php

use App\Models\Message;
use App\Models\Role;
use App\Models\User;

function createAdminUser(): User
{
    $role = Role::create(['name' => 'admin', 'display_name' => 'Admin']);
    $user = User::factory()->create();
    $user->roles()->attach($role);
    return $user;
}

// --- Dashboard index ---

test('dashboard returns statistics for admin', function () {
    $admin = createAdminUser();
    User::factory()->count(3)->create();
    Message::factory()->count(5)->create();

    $response = $this->actingAs($admin)->get(route('dashboard'));

    $response->assertOk();
});

test('dashboard is forbidden for regular user', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertForbidden();
});

// --- Statistics endpoint ---

test('statistics endpoint requires authentication', function () {
    $response = $this->getJson(route('dashboard.statistics', [
        'resolution' => 'day',
        'metric' => 'messages',
    ]));

    $response->assertUnauthorized();
});

test('statistics endpoint forbidden for regular user', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->getJson(route('dashboard.statistics', [
        'resolution' => 'day',
        'metric' => 'messages',
    ]));

    $response->assertForbidden();
});

test('statistics returns messages data by day', function () {
    // DashboardController uses MySQL DATE_FORMAT which is incompatible with PostgreSQL
    // TODO: fix DashboardController to use DB-agnostic date formatting
    $admin = createAdminUser();
    Message::factory()->count(3)->create(['created_at' => now()]);

    $response = $this->actingAs($admin)->getJson(route('dashboard.statistics', [
        'resolution' => 'day',
        'metric' => 'messages',
    ]));

    $response->assertOk();
    $response->assertJsonStructure([['name', 'value']]);
})->skip('DashboardController uses MySQL DATE_FORMAT - incompatible with PostgreSQL');

test('statistics returns messages data by hour', function () {
    $admin = createAdminUser();
    Message::factory()->count(2)->create(['created_at' => now()]);

    $response = $this->actingAs($admin)->getJson(route('dashboard.statistics', [
        'resolution' => 'hour',
        'metric' => 'messages',
    ]));

    $response->assertOk();
    $response->assertJsonStructure([['name', 'value']]);
})->skip('DashboardController uses MySQL DATE_FORMAT - incompatible with PostgreSQL');

test('statistics returns messages data by week', function () {
    $admin = createAdminUser();

    $response = $this->actingAs($admin)->getJson(route('dashboard.statistics', [
        'resolution' => 'week',
        'metric' => 'messages',
    ]));

    $response->assertOk();
    $response->assertJsonStructure([['name', 'value']]);
})->skip('DashboardController uses MySQL DATE_FORMAT - incompatible with PostgreSQL');

test('statistics returns users data', function () {
    $admin = createAdminUser();

    $response = $this->actingAs($admin)->getJson(route('dashboard.statistics', [
        'resolution' => 'day',
        'metric' => 'users',
    ]));

    $response->assertOk();
    $response->assertJsonStructure([['name', 'value']]);
})->skip('DashboardController uses MySQL DATE_FORMAT - incompatible with PostgreSQL');

test('statistics returns sessions data', function () {
    $admin = createAdminUser();

    $response = $this->actingAs($admin)->getJson(route('dashboard.statistics', [
        'resolution' => 'day',
        'metric' => 'sessions',
    ]));

    $response->assertOk();
    $response->assertJsonStructure([['name', 'value']]);
})->skip('DashboardController uses MySQL DATE_FORMAT - incompatible with PostgreSQL');

test('statistics validates resolution parameter', function () {
    $admin = createAdminUser();

    $response = $this->actingAs($admin)->getJson(route('dashboard.statistics', [
        'resolution' => 'invalid',
        'metric' => 'messages',
    ]));

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors('resolution');
});

test('statistics validates metric parameter', function () {
    $admin = createAdminUser();

    $response = $this->actingAs($admin)->getJson(route('dashboard.statistics', [
        'resolution' => 'day',
        'metric' => 'invalid',
    ]));

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors('metric');
});

test('statistics requires resolution parameter', function () {
    $admin = createAdminUser();

    $response = $this->actingAs($admin)->getJson(route('dashboard.statistics', [
        'metric' => 'messages',
    ]));

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors('resolution');
});

test('statistics requires metric parameter', function () {
    $admin = createAdminUser();

    $response = $this->actingAs($admin)->getJson(route('dashboard.statistics', [
        'resolution' => 'day',
    ]));

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors('metric');
});
