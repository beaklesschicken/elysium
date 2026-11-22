<?php

use App\Enums\OrganizationRole;
use App\Livewire\Organizations\Members;
use App\Models\Organization;
use App\Models\Unit;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->organization = Organization::factory()->create();
    $this->owner = User::factory()->inOrganization($this->organization, OrganizationRole::Owner)->create();
    $this->admin = User::factory()->inOrganization($this->organization, OrganizationRole::Admin)->create();
    $this->manager = User::factory()->inOrganization($this->organization, OrganizationRole::Manager)->create(['name' => 'Mia Manager']);
});

it('lists members for admins', function () {
    $this->actingAs($this->admin)->get(route('organization.members'))->assertOk()->assertSee('Mia Manager');
});

it('lets admins change roles below owner', function () {
    $this->actingAs($this->admin);

    Livewire::test(Members::class)->call('changeRole', $this->manager->id, 'admin');

    expect($this->manager->fresh()->roleIn($this->organization))->toBe(OrganizationRole::Admin);
});

it('only lets owners hand out or take away ownership', function () {
    $this->actingAs($this->admin);

    Livewire::test(Members::class)
        ->call('changeRole', $this->manager->id, 'owner')
        ->assertHasErrors('members');
    Livewire::test(Members::class)
        ->call('changeRole', $this->owner->id, 'manager')
        ->assertHasErrors('members');

    expect($this->manager->fresh()->roleIn($this->organization))->toBe(OrganizationRole::Manager)
        ->and($this->owner->fresh()->roleIn($this->organization))->toBe(OrganizationRole::Owner);

    $this->actingAs($this->owner);
    Livewire::test(Members::class)->call('changeRole', $this->manager->id, 'owner')->assertHasNoErrors();

    expect($this->manager->fresh()->roleIn($this->organization))->toBe(OrganizationRole::Owner);
});

it('keeps at least one owner', function () {
    $this->actingAs($this->owner);

    Livewire::test(Members::class)
        ->call('changeRole', $this->owner->id, 'admin')
        ->assertHasErrors('members');

    expect($this->owner->fresh()->roleIn($this->organization))->toBe(OrganizationRole::Owner);
});

it('removes a member and their unit assignments', function () {
    $unit = Unit::factory()->for($this->organization)->create();
    $unit->managers()->attach($this->manager);
    $this->actingAs($this->admin);

    Livewire::test(Members::class)->call('removeMember', $this->manager->id);

    $this->manager->refresh();
    expect($this->manager->belongsToOrganization($this->organization))->toBeFalse()
        ->and($this->manager->current_organization_id)->toBeNull()
        ->and($unit->managers()->count())->toBe(0);

    $this->actingAs($this->manager)->get(route('units.index'))->assertRedirect(route('organizations.none'));
});

it('does not let admins remove themselves or an owner', function () {
    $this->actingAs($this->admin);

    Livewire::test(Members::class)->call('removeMember', $this->admin->id)->assertHasErrors('members');
    Livewire::test(Members::class)->call('removeMember', $this->owner->id)->assertHasErrors('members');

    expect($this->organization->users()->count())->toBe(3);
});

it('ignores people from other organizations', function () {
    $outsider = User::factory()->inOrganization()->create();
    $this->actingAs($this->owner);

    Livewire::test(Members::class)->call('changeRole', $outsider->id, 'admin');
    Livewire::test(Members::class)->call('removeMember', $outsider->id);

    expect($outsider->fresh()->current_organization_id)->not->toBeNull()
        ->and($outsider->belongsToOrganization($this->organization))->toBeFalse();
});

it('blocks managers from member actions', function () {
    $this->actingAs($this->manager);

    Livewire::test(Members::class)->assertForbidden();
});
