<?php

use App\Actions\Fortify\ResetUserPassword;
use App\Models\User;
use Illuminate\Validation\ValidationException;

/*
|--------------------------------------------------------------------------
| ResetUserPassword Action Tests
|--------------------------------------------------------------------------
*/

test('resets user password with valid input', function () {
    $user = User::factory()->create();

    $action = new ResetUserPassword();
    $action->reset($user, [
        'password' => 'new-password',
        'password_confirmation' => 'new-password',
    ]);

    $user->refresh();
    expect(password_verify('new-password', $user->password))->toBeTrue();
});

test('fails with missing password', function () {
    $user = User::factory()->create();
    $action = new ResetUserPassword();

    $action->reset($user, []);
})->throws(ValidationException::class);

test('fails with password mismatch', function () {
    $user = User::factory()->create();
    $action = new ResetUserPassword();

    $action->reset($user, [
        'password' => 'new-password',
        'password_confirmation' => 'different',
    ]);
})->throws(ValidationException::class);

test('password is hashed after reset', function () {
    $user = User::factory()->create();
    $action = new ResetUserPassword();

    $action->reset($user, [
        'password' => 'my-new-password',
        'password_confirmation' => 'my-new-password',
    ]);

    $user->refresh();
    expect($user->password)->not->toBe('my-new-password');
    expect(password_verify('my-new-password', $user->password))->toBeTrue();
});
