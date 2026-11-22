<?php

use App\Models\Credential;
use App\Models\CredentialType;
use App\Models\Employee;
use App\Models\Organization;
use App\Models\Unit;

test('a credential can be held by an employee, unit, or organization', function () {
    $organization = Organization::factory()->create();
    $employee = Employee::factory()->for($organization)->create();
    $unit = Unit::factory()->for($organization)->create();

    Credential::factory()->heldBy($employee)->create();
    Credential::factory()->heldBy($unit)->for(CredentialType::factory()->forUnits(), 'type')->create();
    Credential::factory()->heldBy($organization)->for(CredentialType::factory()->forOrganizations(), 'type')->create();

    expect($employee->credentials)->toHaveCount(1)
        ->and($unit->credentials)->toHaveCount(1)
        ->and($organization->credentials)->toHaveCount(1)
        ->and($employee->credentials->first()->holder->is($employee))->toBeTrue()
        ->and(Credential::pluck('organization_id')->unique()->all())->toBe([$organization->id]);
});
