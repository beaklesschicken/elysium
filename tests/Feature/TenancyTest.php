<?php

use App\Enums\OrganizationRole;
use App\Models\Employee;
use App\Models\Organization;
use App\Models\Unit;
use App\Models\User;

beforeEach(function () {
    $this->ours = Organization::factory()->create();
    $this->theirs = Organization::factory()->create();
    $this->owner = User::factory()->inOrganization($this->ours)->create();

    $this->ourUnit = Unit::factory()->for($this->ours)->create(['name' => 'Our Taproom']);
    $this->theirUnit = Unit::factory()->for($this->theirs)->create(['name' => 'Their Taproom']);
    $this->ourEmployee = Employee::factory()->for($this->ours)->create(['name' => 'Olivia Ours']);
    $this->theirEmployee = Employee::factory()->for($this->theirs)->create(['name' => 'Tom Theirs']);
});

it('only returns records from the current organization', function () {
    $this->actingAs($this->owner);

    expect(Unit::pluck('id')->all())->toBe([$this->ourUnit->id])
        ->and(Employee::pluck('id')->all())->toBe([$this->ourEmployee->id]);
});

it('fills the organization when creating records', function () {
    $this->actingAs($this->owner);

    $unit = Unit::create(['name' => 'New Spot']);

    expect($unit->organization_id)->toBe($this->ours->id);
});

it('does not scope queries when nobody is signed in', function () {
    expect(Unit::count())->toBe(2);
});

it('hides other organizations\' pages', function (string $route, string $model) {
    $record = $this->{$model};

    $this->actingAs($this->owner)
        ->get(route($route, $record))
        ->assertNotFound();
})->with([
    'unit page' => ['units.show', 'theirUnit'],
    'unit edit' => ['units.edit', 'theirUnit'],
    'employee edit' => ['employees.edit', 'theirEmployee'],
]);

it('lists only our organization\'s records', function () {
    $this->actingAs($this->owner);

    $this->get(route('units.index'))->assertSee('Our Taproom')->assertDontSee('Their Taproom');
    $this->get(route('employees.index'))->assertSee('Olivia Ours')->assertDontSee('Tom Theirs');
});

it('switches between organizations the user belongs to', function () {
    $this->theirs->addMember($this->owner, OrganizationRole::Admin);
    $this->actingAs($this->owner);

    $this->post(route('organizations.switch', $this->theirs))->assertRedirect(route('dashboard'));

    expect($this->owner->fresh()->current_organization_id)->toBe($this->theirs->id);
    $this->get(route('units.index'))->assertSee('Their Taproom')->assertDontSee('Our Taproom');
});

it('refuses to switch to an organization the user is not in', function () {
    $this->actingAs($this->owner)
        ->post(route('organizations.switch', $this->theirs))
        ->assertForbidden();

    expect($this->owner->fresh()->current_organization_id)->toBe($this->ours->id);
});

it('moves users off an organization they were removed from', function () {
    $this->theirs->addMember($this->owner, OrganizationRole::Admin);
    $this->owner->forceFill(['current_organization_id' => $this->theirs->id])->save();
    $this->theirs->users()->detach($this->owner);

    $this->actingAs($this->owner->fresh())->get(route('units.index'))->assertOk()->assertSee('Our Taproom');

    expect($this->owner->fresh()->current_organization_id)->toBe($this->ours->id);
});
