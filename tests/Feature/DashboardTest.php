<?php

use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\Unit;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $this->get('/dashboard')->assertRedirect('/login');
});

test('members can visit the dashboard', function () {
    $this->actingAs(User::factory()->inOrganization()->create());

    $this->get('/dashboard')->assertOk();
});

test('users without an organization are sent to set one up', function () {
    $this->actingAs(User::factory()->create());

    $this->get('/dashboard')->assertRedirect(route('organizations.create'));
});

test('admins see the setup checklist until it is done', function () {
    $organization = Organization::factory()->create();
    $this->actingAs(User::factory()->inOrganization($organization)->create());

    $this->get('/dashboard')->assertSee('Get set up')->assertSee('Add your first unit');
});

test('managers only see counts for their own units', function () {
    $organization = Organization::factory()->create();
    $manager = User::factory()->inOrganization($organization, OrganizationRole::Manager)->create();
    Unit::factory()->for($organization)->hasAttached($manager, [], 'managers')->create();
    Unit::factory(2)->for($organization)->create();

    $this->actingAs($manager)->get('/dashboard')
        ->assertOk()
        ->assertViewHas('unitCount', 1)
        ->assertViewHas('memberCount', null)
        ->assertDontSee('Get set up');
});
