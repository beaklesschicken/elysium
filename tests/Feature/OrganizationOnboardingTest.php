<?php

use App\Enums\OrganizationRole;
use App\Livewire\Organizations\CreateOrganization;
use App\Livewire\Organizations\EditOrganization;
use App\Mail\InvitationMail;
use App\Models\Invitation;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

it('lets a site admin create an organization and own it', function () {
    $user = User::factory()->siteAdmin()->create();
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
    $this->actingAs(User::factory()->siteAdmin()->create());

    Livewire::test(CreateOrganization::class)
        ->set('name', 'Valid Name')
        ->set($field, $value)
        ->call('save')
        ->assertHasErrors([$field => $rule]);
})->with([
    'missing name' => ['name', '', 'required'],
    'long state' => ['state_province', 'Indiana', 'regex'],
    'bad zip' => ['postcode', '462', 'regex'],
    'bad owner email' => ['ownerEmail', 'not-an-email', 'email'],
]);

it('lets a site admin create an organization for someone else and invite them as owner', function () {
    Mail::fake();
    $siteAdmin = User::factory()->siteAdmin()->create();
    $this->actingAs($siteAdmin);

    Livewire::test(CreateOrganization::class)
        ->set('name', 'Lakeside Bars')
        ->set('ownerEmail', 'Owner@Lakeside.example')
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('name', '');

    $organization = Organization::firstWhere('name', 'Lakeside Bars');
    $invitation = Invitation::withoutGlobalScopes()->sole();

    expect($organization->users()->count())->toBe(0)
        ->and($invitation->organization_id)->toBe($organization->id)
        ->and($invitation->email)->toBe('owner@lakeside.example')
        ->and($invitation->role)->toBe(OrganizationRole::Owner)
        ->and($siteAdmin->fresh()->current_organization_id)->toBeNull();

    Mail::assertSent(InvitationMail::class, fn ($mail) => $mail->hasTo('owner@lakeside.example'));
});

it('only lets site admins create organizations', function () {
    $owner = User::factory()->inOrganization()->create();
    $this->actingAs($owner);

    $this->get(route('organizations.create'))->assertForbidden();
    $this->get(route('dashboard'))->assertDontSee('New organization');

    $this->actingAs(User::factory()->siteAdmin()->inOrganization()->create());
    $this->get(route('dashboard'))->assertSee('New organization');
});

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
