<?php

use App\Models\Role;
use App\Models\User;
use App\Models\UserMute;

function createMuteTestUserWithRole(string $roleName): User
{
    $role = Role::firstOrCreate(['name' => $roleName], ['display_name' => ucfirst(str_replace('_', ' ', $roleName))]);
    $user = User::factory()->create();
    $user->roles()->attach($role);

    return $user;
}

// --- UserMuteController@store ---

test('moderator can mute a user with duration', function () {
    $moderator = createMuteTestUserWithRole('moderator');
    $target = User::factory()->create();

    $response = $this->actingAs($moderator)->postJson(route('admin.users.mute', $target), [
        'reason' => 'Spamming the chat',
        'duration' => 60,
    ]);

    $response->assertCreated();
    $this->assertDatabaseHas('user_mutes', [
        'user_id' => $target->id,
        'muted_by' => $moderator->id,
        'reason' => 'Spamming the chat',
    ]);

    $mute = UserMute::where('user_id', $target->id)->first();
    expect($mute->expires_at)->not->toBeNull();
});

test('moderator can mute a user permanently', function () {
    $moderator = createMuteTestUserWithRole('moderator');
    $target = User::factory()->create();

    $response = $this->actingAs($moderator)->postJson(route('admin.users.mute', $target), [
        'reason' => 'Permanent ban from chat',
        'duration' => null,
    ]);

    $response->assertCreated();

    $mute = UserMute::where('user_id', $target->id)->first();
    expect($mute->expires_at)->toBeNull();
});

test('regular user cannot mute another user', function () {
    $user = User::factory()->create();
    $target = User::factory()->create();

    $response = $this->actingAs($user)->postJson(route('admin.users.mute', $target), [
        'reason' => 'No permission',
        'duration' => 30,
    ]);

    $response->assertForbidden();
});

test('user cannot mute themselves', function () {
    $moderator = createMuteTestUserWithRole('moderator');

    $response = $this->actingAs($moderator)->postJson(route('admin.users.mute', $moderator), [
        'reason' => 'Self mute',
        'duration' => 10,
    ]);

    $response->assertStatus(422);
});

test('moderator cannot mute an admin', function () {
    $moderator = createMuteTestUserWithRole('moderator');
    $admin = createMuteTestUserWithRole('admin');

    $response = $this->actingAs($moderator)->postJson(route('admin.users.mute', $admin), [
        'reason' => 'Trying to mute admin',
        'duration' => 10,
    ]);

    $response->assertForbidden();
});

// --- UserMuteController@destroy ---

test('admin can unmute a user', function () {
    $admin = createMuteTestUserWithRole('admin');
    $target = User::factory()->create();

    $mute = UserMute::create([
        'user_id' => $target->id,
        'muted_by' => $admin->id,
        'reason' => 'Test mute',
        'expires_at' => now()->addHour(),
    ]);

    $response = $this->actingAs($admin)->deleteJson(route('admin.mutes.destroy', $mute));

    $response->assertOk();
    $this->assertDatabaseMissing('user_mutes', ['id' => $mute->id]);
});
