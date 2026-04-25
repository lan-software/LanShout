<?php

use Illuminate\Support\Facades\View;

/*
|--------------------------------------------------------------------------
| HandleAppearance Middleware Tests
|--------------------------------------------------------------------------
*/

test('shares system appearance when no cookie is set', function () {
    $response = $this->get(route('login'));

    $response->assertOk();

    // The view should receive 'system' as default appearance
    $shared = View::getShared();
    expect($shared['appearance'])->toBe('system');
});

test('shares cookie appearance value when cookie is set', function () {
    $response = $this->withUnencryptedCookies(['appearance' => 'dark'])
        ->get(route('login'));

    $response->assertOk();

    $shared = View::getShared();
    expect($shared['appearance'])->toBe('dark');
});

test('shares light appearance when light cookie is set', function () {
    $response = $this->withUnencryptedCookies(['appearance' => 'light'])
        ->get(route('login'));

    $response->assertOk();

    $shared = View::getShared();
    expect($shared['appearance'])->toBe('light');
});
