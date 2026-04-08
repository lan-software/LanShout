<?php

use App\Models\User;
use Illuminate\Support\Facades\Redis;

it('writes the demo activity marker on authenticated requests when demo mode is on', function (): void {
    config()->set('app.demo', true);
    Redis::shouldReceive('set')
        ->once()
        ->with('demo:last_activity', Mockery::type('string'));

    $user = User::factory()->create();

    $this->actingAs($user)->get('/');
});

it('does not write the marker when demo mode is off', function (): void {
    config()->set('app.demo', false);
    Redis::shouldReceive('set')->never();

    $user = User::factory()->create();

    $this->actingAs($user)->get('/');
});

it('does not write the marker for unauthenticated requests', function (): void {
    config()->set('app.demo', true);
    Redis::shouldReceive('set')->never();

    $this->get('/');
});
