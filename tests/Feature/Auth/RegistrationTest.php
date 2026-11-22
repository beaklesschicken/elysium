<?php

use App\Livewire\Auth\Register;
use Livewire\Livewire;

test('public sign-up is closed by default', function () {
    expect(config('app.registration_open'))->toBeFalse();

    $this->get('/register')->assertNotFound();
    $this->get('/login')->assertOk()->assertDontSee('Sign up');
});

test('the sign-up action is refused while sign-up is closed', function () {
    Livewire::test(Register::class)
        ->set('name', 'Test User')
        ->set('email', 'test@example.com')
        ->set('password', 'password')
        ->set('password_confirmation', 'password')
        ->call('register')
        ->assertNotFound();

    $this->assertGuest();
});

test('registration screen can be rendered when sign-up is open', function () {
    config(['app.registration_open' => true]);

    $this->get('/register')->assertOk();
});

test('new users can register when sign-up is open', function () {
    config(['app.registration_open' => true]);

    $response = Livewire::test(Register::class)
        ->set('name', 'Test User')
        ->set('email', 'test@example.com')
        ->set('password', 'password')
        ->set('password_confirmation', 'password')
        ->call('register');

    $response
        ->assertHasNoErrors()
        ->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticated();
});
