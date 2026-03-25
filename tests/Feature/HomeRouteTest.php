<?php

use App\Models\User;

test('guest sees landing page', function () {
    $response = $this->get(route('home'));

    $response->assertOk();
});

test('authenticated user is redirected from home to chat', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('home'));

    $response->assertRedirect(route('chat'));
});
