<?php

it('returns 404 or 405 for POST /register when demo mode is on', function (): void {
    config()->set('app.demo', true);

    $response = $this->post('/register', [
        'name' => 'Test',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    expect($response->status())->toBeIn([403, 404, 405]);
});
