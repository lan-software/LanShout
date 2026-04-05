<?php

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    config([
        'lancore.enabled' => true,
        'lancore.internal_url' => null,
        'lancore.base_url' => 'http://lancore.test',
        'lancore.token' => 'lci_test_token',
        'lancore.app_slug' => 'lanshout',
    ]);
});

it('redirects to LanCore SSO when enabled', function () {
    $this->get(route('auth.redirect'))
        ->assertRedirectContains('lancore.test/sso/authorize');
});

it('creates a local user from a valid LanCore callback', function () {
    Http::fake([
        '*/api/integration/sso/exchange' => Http::response([
            'data' => [
                'id' => 42,
                'username' => 'chat-admin',
                'email' => 'chat-admin@example.com',
                'locale' => 'en',
                'avatar_url' => null,
                'roles' => ['admin'],
            ],
        ]),
    ]);

    $this->get(route('auth.callback', ['code' => str_repeat('a', 64)]))
        ->assertRedirect(route('chat'));

    $user = User::query()->where('lancore_user_id', 42)->first();

    expect($user)->not->toBeNull()
        ->and($user?->email)->toBe('chat-admin@example.com')
        ->and($user?->hasRole('admin'))->toBeTrue();
});

it('redirects the Fortify login page to LanCore when enabled', function () {
    $this->get(route('login'))
        ->assertRedirect(route('auth.redirect'));
});