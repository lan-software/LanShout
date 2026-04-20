<?php

use App\Models\User;

/*
|--------------------------------------------------------------------------
| ProfileUpdateRequest Validation Tests
|--------------------------------------------------------------------------
*/

test('profile update accepts valid data', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->patch(route('profile.update'), [
        'name' => 'Updated Name',
        'email' => 'updated@example.com',
    ]);

    $response->assertRedirect(route('profile.edit'));
    expect($user->fresh()->name)->toBe('Updated Name');
});

test('profile update requires name', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->patch(route('profile.update'), [
        'name' => '',
        'email' => 'updated@example.com',
    ]);

    $response->assertSessionHasErrors('name');
});

test('profile update requires email', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->patch(route('profile.update'), [
        'name' => 'Updated',
        'email' => '',
    ]);

    $response->assertSessionHasErrors('email');
});

test('profile update rejects invalid email', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->patch(route('profile.update'), [
        'name' => 'Updated',
        'email' => 'not-an-email',
    ]);

    $response->assertSessionHasErrors('email');
});

test('profile update rejects duplicate email', function () {
    $existing = User::factory()->create(['email' => 'taken@example.com']);
    $user = User::factory()->create();

    $response = $this->actingAs($user)->patch(route('profile.update'), [
        'name' => 'Updated',
        'email' => 'taken@example.com',
    ]);

    $response->assertSessionHasErrors('email');
});

test('profile update allows own email', function () {
    $user = User::factory()->create(['email' => 'mine@example.com']);

    $response = $this->actingAs($user)->patch(route('profile.update'), [
        'name' => 'Updated Name',
        'email' => 'mine@example.com',
    ]);

    $response->assertRedirect(route('profile.edit'));
});

test('profile update accepts valid chat color', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->patch(route('profile.update'), [
        'name' => 'Updated',
        'email' => $user->email,
        'chat_color' => '#FF5733',
    ]);

    $response->assertRedirect(route('profile.edit'));
    expect($user->fresh()->chat_color)->toBe('#FF5733');
});

test('profile update rejects invalid chat color', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->patch(route('profile.update'), [
        'name' => 'Updated',
        'email' => $user->email,
        'chat_color' => 'red',
    ]);

    $response->assertSessionHasErrors('chat_color');
});

test('profile update rejects short hex color', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->patch(route('profile.update'), [
        'name' => 'Updated',
        'email' => $user->email,
        'chat_color' => '#FFF',
    ]);

    $response->assertSessionHasErrors('chat_color');
});

test('profile update accepts valid locale', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->patch(route('profile.update'), [
        'name' => 'Updated',
        'email' => $user->email,
        'locale' => 'de',
    ]);

    $response->assertRedirect(route('profile.edit'));
    expect($user->fresh()->locale)->toBe('de');
});

test('profile update rejects invalid locale', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->patch(route('profile.update'), [
        'name' => 'Updated',
        'email' => $user->email,
        'locale' => 'zz',
    ]);

    $response->assertSessionHasErrors('locale');
});

test('profile update allows null chat color', function () {
    $user = User::factory()->create(['chat_color' => '#FF0000']);

    $response = $this->actingAs($user)->patch(route('profile.update'), [
        'name' => 'Updated',
        'email' => $user->email,
        'chat_color' => null,
    ]);

    $response->assertRedirect(route('profile.edit'));
});

test('profile update allows null locale', function () {
    $user = User::factory()->create(['locale' => 'de']);

    $response = $this->actingAs($user)->patch(route('profile.update'), [
        'name' => 'Updated',
        'email' => $user->email,
        'locale' => null,
    ]);

    $response->assertRedirect(route('profile.edit'));
});
