<?php

use App\Enums\OrganizationRole;
use App\Livewire\Organizations\Members;
use App\Models\Employee;
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

it('puts a member on the employee list to track their own credentials', function () {
    $this->actingAs($this->owner);

    Livewire::test(Members::class)
        ->call('trackAsEmployee', $this->owner->id)
        ->assertRedirect();

    $employee = Employee::query()->where('user_id', $this->owner->id)->sole();

    expect($employee->name)->toBe($this->owner->name)
        ->and($employee->email)->toBe($this->owner->email)
        ->and($employee->organization_id)->toBe($this->organization->id)
        ->and($employee->portalRole())->toBe(OrganizationRole::Owner);

    $this->get(route('employees.index'))->assertOk()->assertSee($this->owner->name)->assertSee('Owner');
});

it('adds a tracked manager to the units they manage', function () {
    $unit = Unit::factory()->for($this->organization)->create();
    $unit->managers()->attach($this->manager);
    $this->actingAs($this->admin);

    Livewire::test(Members::class)->call('trackAsEmployee', $this->manager->id);

    $employee = Employee::query()->where('user_id', $this->manager->id)->sole();
    expect($employee->units->modelKeys())->toBe([$unit->id]);
});

it('links an existing employee with the same email instead of duplicating', function () {
    $existing = Employee::factory()->for($this->organization)->create(['email' => $this->admin->email]);
    $this->actingAs($this->owner);

    Livewire::test(Members::class)->call('trackAsEmployee', $this->admin->id);
    Livewire::test(Members::class)->call('trackAsEmployee', $this->admin->id);

    expect($existing->fresh()->user_id)->toBe($this->admin->id)
        ->and(Employee::query()->where('user_id', $this->admin->id)->count())->toBe(1);
});

it('does not track people outside the organization', function () {
    $outsider = User::factory()->inOrganization(Organization::factory()->create(), OrganizationRole::Owner)->create();
    $this->actingAs($this->owner);

    Livewire::test(Members::class)->call('trackAsEmployee', $outsider->id);

    expect(Employee::query()->where('user_id', $outsider->id)->exists())->toBeFalse();
});

it('does not let managers track members', function () {
    $this->actingAs($this->manager);

    Livewire::test(Members::class)->assertForbidden();
});
