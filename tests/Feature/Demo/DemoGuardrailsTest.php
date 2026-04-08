<?php

beforeEach(function (): void {
    config()->set('app.demo', true);
});

it('blocks registration POST in demo mode', function (): void {
    $response = $this->post('/register', [
        'name' => 'Test',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    expect($response->status())->toBeIn([403, 404, 405]);
});

it('blocks profile-information PUT in demo mode', function (): void {
    $response = $this->put('/user/profile-information', [
        'name' => 'Test',
        'email' => 'test@example.com',
    ]);

    expect($response->status())->toBeIn([403, 404, 405]);
});

it('blocks password PUT in demo mode', function (): void {
    $response = $this->put('/user/password', []);

    expect($response->status())->toBeIn([403, 404, 405]);
});

it('blocks user DELETE in demo mode', function (): void {
    $response = $this->delete('/user');

    expect($response->status())->toBeIn([403, 404, 405]);
});
