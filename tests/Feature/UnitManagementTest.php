<?php

use App\Enums\OrganizationRole;
use App\Livewire\Units\Show;
use App\Livewire\Units\UnitForm;
use App\Models\Employee;
use App\Models\Organization;
use App\Models\Unit;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->organization = Organization::factory()->create();
    $this->admin = User::factory()->inOrganization($this->organization, OrganizationRole::Admin)->create();
    $this->manager = User::factory()->inOrganization($this->organization, OrganizationRole::Manager)->create();
    $this->managedUnit = Unit::factory()->for($this->organization)->create(['name' => 'Managed Bar']);
    $this->otherUnit = Unit::factory()->for($this->organization)->create(['name' => 'Other Bar']);
    $this->managedUnit->managers()->attach($this->manager);
});

it('lets admins create a unit', function () {
    $this->actingAs($this->admin);

    Livewire::test(UnitForm::class)
        ->set('name', 'Rooftop Lounge')
        ->set('city', 'Carmel')
        ->set('state_province', 'IN')
        ->call('save')
        ->assertHasNoErrors();

    $unit = Unit::firstWhere('name', 'Rooftop Lounge');
    expect($unit->organization_id)->toBe($this->organization->id);
});

it('requires a unit name', function () {
    $this->actingAs($this->admin);

    Livewire::test(UnitForm::class)->set('name', '')->call('save')->assertHasErrors(['name' => 'required']);
});

it('lets admins edit a unit', function () {
    $this->actingAs($this->admin);

    Livewire::test(UnitForm::class, ['unit' => $this->managedUnit])
        ->assertSet('name', 'Managed Bar')
        ->set('name', 'Renamed Bar')
        ->call('save')
        ->assertRedirect(route('units.show', $this->managedUnit, absolute: false));

    expect($this->managedUnit->fresh()->name)->toBe('Renamed Bar');
});

it('stops managers from creating or editing units', function () {
    $this->actingAs($this->manager);

    $this->get(route('units.create'))->assertForbidden();
    $this->get(route('units.edit', $this->managedUnit))->assertForbidden();
});

it('shows managers only the units they manage', function () {
    $this->actingAs($this->manager);

    $this->get(route('units.index'))->assertSee('Managed Bar')->assertDontSee('Other Bar');
    $this->get(route('units.show', $this->managedUnit))->assertOk();
    $this->get(route('units.show', $this->otherUnit))->assertForbidden();
});

it('lets admins assign and remove managers', function () {
    $this->actingAs($this->admin);

    Livewire::test(Show::class, ['unit' => $this->otherUnit])
        ->set('managerToAdd', (string) $this->manager->id)
        ->call('addManager')
        ->assertHasNoErrors();

    expect($this->manager->managesUnit($this->otherUnit))->toBeTrue();

    Livewire::test(Show::class, ['unit' => $this->otherUnit])->call('removeManager', $this->manager->id);

    expect($this->manager->managesUnit($this->otherUnit))->toBeFalse();
});

it('refuses managers from outside the organization', function () {
    $outsider = User::factory()->inOrganization()->create();
    $this->actingAs($this->admin);

    Livewire::test(Show::class, ['unit' => $this->otherUnit])
        ->set('managerToAdd', (string) $outsider->id)
        ->call('addManager')
        ->assertHasErrors(['managerToAdd' => 'in']);
});

it('stops managers from changing who manages a unit', function () {
    $this->actingAs($this->manager);

    Livewire::test(Show::class, ['unit' => $this->managedUnit])
        ->call('removeManager', $this->manager->id)
        ->assertForbidden();
});

it('lets managers put employees on and off their unit', function () {
    $employee = Employee::factory()->for($this->organization)->create();
    $employee->units()->attach($this->managedUnit);
    $secondUnit = Unit::factory()->for($this->organization)->create();
    $secondUnit->managers()->attach($this->manager);
    $this->actingAs($this->manager);

    Livewire::test(Show::class, ['unit' => $secondUnit])
        ->set('employeeToAdd', (string) $employee->id)
        ->call('addEmployee')
        ->assertHasNoErrors();

    expect($employee->units()->pluck('units.id')->sort()->values()->all())
        ->toBe([$this->managedUnit->id, $secondUnit->id]);

    Livewire::test(Show::class, ['unit' => $secondUnit])->call('removeEmployee', $employee->id);

    expect($employee->units()->pluck('units.id')->all())->toBe([$this->managedUnit->id]);
});

it('refuses employees from another organization', function () {
    $stranger = Employee::factory()->create();
    $this->actingAs($this->admin);

    Livewire::test(Show::class, ['unit' => $this->managedUnit])
        ->set('employeeToAdd', (string) $stranger->id)
        ->call('addEmployee')
        ->assertHasErrors(['employeeToAdd' => 'in']);
});
