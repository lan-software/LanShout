<?php

use App\Actions\Fortify\CreateNewUser;
use App\Models\User;
use Illuminate\Validation\ValidationException;

/*
|--------------------------------------------------------------------------
| CreateNewUser Action Tests
|--------------------------------------------------------------------------
*/

test('creates a new user with valid input', function () {
    $action = new CreateNewUser;

    $user = $action->create([
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    expect($user)->toBeInstanceOf(User::class);
    expect($user->name)->toBe('Jane Doe');
    expect($user->email)->toBe('jane@example.com');
    expect($user->exists)->toBeTrue();
});

test('fails with missing name', function () {
    $action = new CreateNewUser;

    $action->create([
        'email' => 'jane@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);
})->throws(ValidationException::class);

test('fails with missing email', function () {
    $action = new CreateNewUser;

    $action->create([
        'name' => 'Jane Doe',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);
})->throws(ValidationException::class);

test('fails with invalid email format', function () {
    $action = new CreateNewUser;

    $action->create([
        'name' => 'Jane Doe',
        'email' => 'not-an-email',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);
})->throws(ValidationException::class);

test('fails with duplicate email', function () {
    User::factory()->create(['email' => 'taken@example.com']);

    $action = new CreateNewUser;

    $action->create([
        'name' => 'Jane Doe',
        'email' => 'taken@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);
})->throws(ValidationException::class);

test('fails with password mismatch', function () {
    $action = new CreateNewUser;

    $action->create([
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'password' => 'password',
        'password_confirmation' => 'different',
    ]);
})->throws(ValidationException::class);

test('fails with missing password', function () {
    $action = new CreateNewUser;

    $action->create([
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
    ]);
})->throws(ValidationException::class);

test('password is hashed when user is created', function () {
    $action = new CreateNewUser;

    $user = $action->create([
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    expect($user->password)->not->toBe('password');
    expect(password_verify('password', $user->password))->toBeTrue();
});
