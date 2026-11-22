<?php

use App\Enums\EmployeeStatus;
use App\Enums\OrganizationRole;
use App\Livewire\Employees\EmployeeForm;
use App\Livewire\Employees\Index;
use App\Models\Employee;
use App\Models\Organization;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Validation\Rules\Enum;
use Livewire\Livewire;

beforeEach(function () {
    $this->organization = Organization::factory()->create();
    $this->admin = User::factory()->inOrganization($this->organization, OrganizationRole::Admin)->create();
    $this->manager = User::factory()->inOrganization($this->organization, OrganizationRole::Manager)->create();
    $this->managedUnit = Unit::factory()->for($this->organization)->create();
    $this->otherUnit = Unit::factory()->for($this->organization)->create();
    $this->managedUnit->managers()->attach($this->manager);
});

it('lets admins add an employee to units', function () {
    $this->actingAs($this->admin);

    Livewire::test(EmployeeForm::class)
        ->set('name', 'Jamie Pourwell')
        ->set('email', 'jamie@example.com')
        ->set('start_date', '2025-04-01')
        ->set('birth_date', '1998-06-15')
        ->set('unitIds', [(string) $this->managedUnit->id, (string) $this->otherUnit->id])
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('employees.index', absolute: false));

    $employee = Employee::firstWhere('name', 'Jamie Pourwell');

    expect($employee->organization_id)->toBe($this->organization->id)
        ->and($employee->status)->toBe(EmployeeStatus::Active)
        ->and($employee->start_date->toDateString())->toBe('2025-04-01')
        ->and($employee->units()->count())->toBe(2);
});

it('preselects the unit from the link', function () {
    $this->actingAs($this->admin);

    Livewire::withQueryParams(['unit' => $this->otherUnit->id])
        ->test(EmployeeForm::class)
        ->assertSet('unitIds', [(string) $this->otherUnit->id]);
});

it('validates employee details', function (string $field, mixed $value, string $rule) {
    $this->actingAs($this->admin);

    Livewire::test(EmployeeForm::class)
        ->set('name', 'Valid Name')
        ->set($field, $value)
        ->call('save')
        ->assertHasErrors([$field => $rule]);
})->with([
    'missing name' => ['name', '', 'required'],
    'bad email' => ['email', 'not-an-email', 'email'],
    'future birth date' => ['birth_date', '2999-01-01', 'before'],
    'bad status' => ['status', 'fired', Enum::class],
]);

it('makes managers put new employees on one of their units', function () {
    $this->actingAs($this->manager);

    Livewire::test(EmployeeForm::class)
        ->assertSet('unitIds', [(string) $this->managedUnit->id])
        ->set('name', 'No Unit')
        ->set('unitIds', [])
        ->call('save')
        ->assertHasErrors(['unitIds' => 'required']);

    Livewire::test(EmployeeForm::class)
        ->set('name', 'Wrong Unit')
        ->set('unitIds', [(string) $this->otherUnit->id])
        ->call('save')
        ->assertHasErrors(['unitIds.0' => 'in']);
});

it('keeps units a manager cannot see when they edit an employee', function () {
    $employee = Employee::factory()->for($this->organization)->create();
    $employee->units()->attach([$this->managedUnit->id, $this->otherUnit->id]);
    $this->actingAs($this->manager);

    Livewire::test(EmployeeForm::class, ['employee' => $employee])
        ->assertSet('unitIds', [(string) $this->managedUnit->id])
        ->set('status', 'inactive')
        ->call('save')
        ->assertHasNoErrors();

    expect($employee->fresh()->status)->toBe(EmployeeStatus::Inactive)
        ->and($employee->units()->pluck('units.id')->sort()->values()->all())
        ->toBe([$this->managedUnit->id, $this->otherUnit->id]);
});

it('stops managers from opening employees outside their units', function () {
    $employee = Employee::factory()->for($this->organization)->create();
    $employee->units()->attach($this->otherUnit);

    $this->actingAs($this->manager)->get(route('employees.edit', $employee))->assertForbidden();
});

it('only lets admins delete employees', function () {
    $employee = Employee::factory()->for($this->organization)->create();
    $employee->units()->attach($this->managedUnit);

    $this->actingAs($this->manager);
    Livewire::test(EmployeeForm::class, ['employee' => $employee])->call('delete')->assertForbidden();

    $this->actingAs($this->admin);
    Livewire::test(EmployeeForm::class, ['employee' => $employee])->call('delete');

    expect(Employee::find($employee->id))->toBeNull();
});

it('filters the employee list', function () {
    $this->actingAs($this->admin);
    Employee::factory()->for($this->organization)->create(['name' => 'Avery Active']);
    Employee::factory()->for($this->organization)->inactive()->create(['name' => 'Ian Inactive']);
    $onOtherUnit = Employee::factory()->for($this->organization)->create(['name' => 'Olive Otherunit']);
    $onOtherUnit->units()->attach($this->otherUnit);

    Livewire::test(Index::class)
        ->assertSee('Avery Active')
        ->assertDontSee('Ian Inactive')
        ->set('status', 'all')
        ->assertSee('Ian Inactive')
        ->set('search', 'avery')
        ->assertSee('Avery Active')
        ->assertDontSee('Ian Inactive')
        ->set('search', '')
        ->set('unit', (string) $this->otherUnit->id)
        ->assertSee('Olive Otherunit')
        ->assertDontSee('Avery Active');
});
