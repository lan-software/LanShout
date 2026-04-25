<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function lanShoutRolesWebhookHeaders(string $body, string $secret): array
{
    return [
        'X-Webhook-Event' => 'user.roles_updated',
        'X-Webhook-Signature' => 'sha256='.hash_hmac('sha256', $body, $secret),
        'Content-Type' => 'application/json',
    ];
}

beforeEach(function () {
    config(['lancore.webhooks.secret' => 'lanshout-webhook-secret']);
});

it('syncs LanShout roles from the LanCore webhook payload', function () {
    $user = User::factory()->lanCoreUser(42)->create();

    $body = json_encode([
        'event' => 'user.roles_updated',
        'user' => [
            'id' => 42,
            'username' => $user->name,
            'roles' => ['superadmin', 'moderator'],
        ],
        'changes' => [
            'added' => ['superadmin'],
            'removed' => ['user'],
        ],
    ], JSON_THROW_ON_ERROR);

    $this->postJson('/api/webhooks/roles', json_decode($body, true), lanShoutRolesWebhookHeaders($body, 'lanshout-webhook-secret'))
        ->assertOk();

    expect($user->fresh()->hasRole('super_admin'))->toBeTrue()
        ->and($user->fresh()->hasRole('moderator'))->toBeTrue();
});

it('rejects a roles webhook with an invalid signature', function () {
    $user = User::factory()->lanCoreUser(42)->create();

    $this->postJson('/api/webhooks/roles', [
        'event' => 'user.roles_updated',
        'user' => [
            'id' => $user->lancore_user_id,
            'username' => $user->name,
            'roles' => ['admin'],
        ],
        'changes' => [
            'added' => ['admin'],
            'removed' => [],
        ],
    ], [
        'X-Webhook-Event' => 'user.roles_updated',
        'X-Webhook-Signature' => 'sha256=invalid',
    ])->assertUnauthorized();
});
