<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

it('creates a verified site admin with a prompted password', function () {
    $this->artisan('users:site-admin', ['email' => 'Boss@Example.com'])
        ->expectsQuestion('Name', 'Site Boss')
        ->expectsQuestion('Password', 'a-long-password')
        ->assertSuccessful();

    $user = User::firstWhere('email', 'boss@example.com');

    expect($user->isSiteAdmin())->toBeTrue()
        ->and($user->hasVerifiedEmail())->toBeTrue()
        ->and(Hash::check('a-long-password', $user->password))->toBeTrue();
});

it('promotes an existing user without changing their password', function () {
    $user = User::factory()->create(['email' => 'existing@example.com']);
    $passwordHash = $user->password;

    $this->artisan('users:site-admin', ['email' => 'existing@example.com'])->assertSuccessful();

    expect($user->fresh()->isSiteAdmin())->toBeTrue()
        ->and($user->fresh()->password)->toBe($passwordHash);
});

it('rejects an invalid email', function () {
    $this->artisan('users:site-admin', ['email' => 'nope'])->assertFailed();

    expect(User::count())->toBe(0);
});

it('does not let site admin be mass assigned', function () {
    $user = User::create(['name' => 'x', 'email' => 'x@example.com', 'password' => 'secret-pass', 'is_site_admin' => true]);

    expect($user->fresh()->isSiteAdmin())->toBeFalse();
});
