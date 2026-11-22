<?php

use App\Models\User;

it('serves the public pages', function (string $path) {
    config(['app.portal_enabled' => false]);

    $this->get($path)->assertSuccessful();
})->with(['home' => '/', 'contact' => '/contact']);

it('hides every portal page when the portal is off', function (string $path) {
    config(['app.portal_enabled' => false]);

    $this->get($path)->assertNotFound();
})->with([
    'login' => '/login',
    'register' => '/register',
    'forgot password' => '/forgot-password',
    'reset password' => '/reset-password/some-token',
    'dashboard' => '/dashboard',
    'units' => '/units',
    'unit by id' => '/units/1',
    'new unit' => '/units/create',
    'employees' => '/employees',
    'credentials' => '/credentials',
    'members' => '/organization/members',
    'new organization' => '/organizations/create',
    'invitation' => '/invitations/some-token',
    'settings' => '/settings/profile',
]);

it('hides portal pages from logged-in users too when the portal is off', function () {
    config(['app.portal_enabled' => false]);

    $this->actingAs(User::factory()->create())
        ->get('/settings/profile')
        ->assertNotFound();
});

it('rejects portal form posts when the portal is off', function () {
    config(['app.portal_enabled' => false]);

    $this->post('/logout')->assertNotFound();
});

it('sends guests to the login page for protected portal pages', function (string $path) {
    $this->get($path)->assertRedirect('/login');
})->with([
    'dashboard' => '/dashboard',
    'units' => '/units',
    'unit by id' => '/units/1',
    'new unit' => '/units/create',
    'employees' => '/employees',
    'employee import' => '/employees/import',
    'employee profile' => '/employees/1',
    'credentials' => '/credentials',
    'credential catalog' => '/credentials/types',
    'organization permits' => '/organization/permits',
    'members' => '/organization/members',
    'organization details' => '/organization',
    'new organization' => '/organizations/create',
    'settings' => '/settings/profile',
]);

it('keeps the portal off unless it is switched on', function () {
    expect((bool) env('APP_PORTAL_ENABLED', false))->toBeTrue()
        ->and(config('app.portal_enabled'))->toBeTrue();

    $configFile = file_get_contents(config_path('app.php'));

    expect($configFile)->toContain("env('APP_PORTAL_ENABLED', false)");
});
