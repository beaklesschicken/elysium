<?php

use App\Enums\OrganizationRole;
use App\Livewire\Organizations\CreateOrganization;
use App\Livewire\Organizations\EditOrganization;
use App\Models\Organization;
use App\Models\User;
use Livewire\Livewire;

it('lets a new user create their organization and become its owner', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $this->get(route('organizations.create'))->assertOk();

    Livewire::test(CreateOrganization::class)
        ->set('name', 'Copper Kettle Group')
        ->set('city', 'Indianapolis')
        ->set('state_province', 'in')
        ->set('postcode', '46204')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('dashboard', absolute: false));

    $organization = Organization::firstWhere('name', 'Copper Kettle Group');
    $user->refresh();

    expect($organization->state_province)->toBe('IN')
        ->and($user->current_organization_id)->toBe($organization->id)
        ->and($user->roleIn($organization))->toBe(OrganizationRole::Owner);
});

it('validates the organization form', function (string $field, string $value, string $rule) {
    $this->actingAs(User::factory()->create());

    Livewire::test(CreateOrganization::class)
        ->set('name', 'Valid Name')
        ->set($field, $value)
        ->call('save')
        ->assertHasErrors([$field => $rule]);
})->with([
    'missing name' => ['name', '', 'required'],
    'long state' => ['state_province', 'Indiana', 'regex'],
    'bad zip' => ['postcode', '462', 'regex'],
]);

it('lets owners and admins edit organization details', function (OrganizationRole $role) {
    $organization = Organization::factory()->create();
    $this->actingAs(User::factory()->inOrganization($organization, $role)->create());

    Livewire::test(EditOrganization::class)
        ->set('name', 'Renamed Group')
        ->call('save')
        ->assertHasNoErrors();

    expect($organization->fresh()->name)->toBe('Renamed Group');
})->with([OrganizationRole::Owner, OrganizationRole::Admin]);

it('keeps managers out of organization settings', function () {
    $organization = Organization::factory()->create();
    $this->actingAs(User::factory()->inOrganization($organization, OrganizationRole::Manager)->create());

    $this->get(route('organization.edit'))->assertForbidden();
    $this->get(route('organization.members'))->assertForbidden();
});
