<?php

use App\Models\User;

/*
|--------------------------------------------------------------------------
| User Model Unit Tests
|--------------------------------------------------------------------------
*/

test('user has correct fillable attributes', function () {
    $user = new User();

    expect($user->getFillable())->toBe(['name', 'email', 'password', 'chat_color', 'locale', 'lancore_user_id']);
});

test('user has correct hidden attributes', function () {
    $user = new User();

    expect($user->getHidden())->toBe(['password', 'remember_token']);
});

test('user casts email_verified_at to datetime', function () {
    $user = new User();
    $casts = $user->getCasts();

    expect($casts)->toHaveKey('email_verified_at');
    expect($casts['email_verified_at'])->toBe('datetime');
});

test('user casts password to hashed', function () {
    $user = new User();
    $casts = $user->getCasts();

    expect($casts)->toHaveKey('password');
    expect($casts['password'])->toBe('hashed');
});

test('user has roles relationship method', function () {
    $user = new User();

    expect(method_exists($user, 'roles'))->toBeTrue();
});

test('user implements MustVerifyEmail', function () {
    $user = new User();

    expect($user)->toBeInstanceOf(\Illuminate\Contracts\Auth\MustVerifyEmail::class);
});

test('user uses HasFactory trait', function () {
    expect(method_exists(User::class, 'factory'))->toBeTrue();
});

test('user uses Notifiable trait', function () {
    $user = new User();

    expect(method_exists($user, 'notify'))->toBeTrue();
});
