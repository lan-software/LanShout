<?php

use App\Models\User;

test('set locale middleware applies the user locale', function () {
    $user = User::factory()->create(['locale' => 'fr']);

    $this->actingAs($user)->get(route('chat'))->assertOk();

    expect(app()->getLocale())->toBe('fr');
});

test('set locale middleware falls back when user has no locale', function () {
    $user = User::factory()->create(['locale' => null]);

    $this->actingAs($user)->get(route('chat'))->assertOk();

    expect(app()->getLocale())->toBe(config('app.fallback_locale'));
});

test('profile update accepts all four locales', function (string $locale) {
    $user = User::factory()->create(['locale' => 'en']);

    $this->actingAs($user)
        ->patch(route('profile.update'), [
            'name' => $user->name,
            'email' => $user->email,
            'locale' => $locale,
        ])
        ->assertSessionHasNoErrors();

    expect($user->refresh()->locale)->toBe($locale);
})->with(['en', 'de', 'fr', 'es']);

test('profile update rejects unsupported locale', function () {
    $user = User::factory()->create(['locale' => 'en']);

    $this->actingAs($user)
        ->patch(route('profile.update'), [
            'name' => $user->name,
            'email' => $user->email,
            'locale' => 'zz',
        ])
        ->assertSessionHasErrors('locale');
});
