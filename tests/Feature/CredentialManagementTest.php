<?php

use App\Enums\OrganizationRole;
use App\Livewire\Credentials\HolderCredentials;
use App\Models\Credential;
use App\Models\CredentialType;
use App\Models\Employee;
use App\Models\Organization;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    $this->organization = Organization::factory()->create();
    $this->admin = User::factory()->inOrganization($this->organization, OrganizationRole::Admin)->create();
    $this->manager = User::factory()->inOrganization($this->organization, OrganizationRole::Manager)->create();
    $this->unit = Unit::factory()->for($this->organization)->create();
    $this->unit->managers()->attach($this->manager);
    $this->employee = Employee::factory()->for($this->organization)->create();
    $this->employee->units()->attach($this->unit);
    $this->permitType = CredentialType::factory()->create(['name' => 'Server permit', 'validity_months' => 24]);
});

it('fills a restricted permit\'s expiry from the employee\'s 21st birthday', function () {
    $this->employee->update(['birth_date' => '2006-03-15']);
    $restricted = CredentialType::factory()->create(['validity_months' => null, 'expires_at_age' => 21]);
    $this->actingAs($this->manager);

    Livewire::test(HolderCredentials::class, ['holder' => $this->employee])
        ->call('startCreate')
        ->set('credentialTypeId', (string) $restricted->id)
        ->assertSet('expiresOn', '2027-03-15');
});

it('adds a credential with a document', function () {
    Storage::fake('local');
    $this->actingAs($this->manager);

    Livewire::test(HolderCredentials::class, ['holder' => $this->employee])
        ->call('startCreate')
        ->set('credentialTypeId', (string) $this->permitType->id)
        ->set('number', 'SP-123')
        ->set('issuedOn', '2025-03-01')
        ->assertSet('expiresOn', '2027-03-01')
        ->set('verified', true)
        ->set('document', UploadedFile::fake()->create('permit.pdf', 200, 'application/pdf'))
        ->call('save')
        ->assertHasNoErrors();

    $credential = $this->employee->credentials()->sole();

    expect($credential->number)->toBe('SP-123')
        ->and($credential->organization_id)->toBe($this->organization->id)
        ->and($credential->expires_on->toDateString())->toBe('2027-03-01')
        ->and($credential->verified_by)->toBe($this->manager->id)
        ->and($credential->documents)->toHaveCount(1);

    Storage::disk('local')->assertExists($credential->documents->first()->path);
    expect($credential->documents->first()->path)->toStartWith('credentials/'.$this->organization->id.'/');
});

it('validates the credential form', function (string $field, mixed $value, string $rule) {
    $this->actingAs($this->admin);

    Livewire::test(HolderCredentials::class, ['holder' => $this->employee])
        ->set('credentialTypeId', (string) $this->permitType->id)
        ->set('issuedOn', '2025-03-01')
        ->set($field, $value)
        ->call('save')
        ->assertHasErrors([$field => $rule]);
})->with([
    'missing type' => ['credentialTypeId', '', 'required'],
    'expires before issued' => ['expiresOn', '2025-01-01', 'after_or_equal'],
    'issued in the future' => ['issuedOn', '2999-01-01', 'before_or_equal'],
    'not a document' => ['document', UploadedFile::fake()->create('virus.exe', 10), 'mimes'],
]);

it('only offers types for the right kind of holder', function () {
    $unitType = CredentialType::factory()->forUnits()->create();
    $this->actingAs($this->admin);

    Livewire::test(HolderCredentials::class, ['holder' => $this->employee])
        ->set('credentialTypeId', (string) $unitType->id)
        ->call('save')
        ->assertHasErrors(['credentialTypeId' => 'in']);
});

it('does not offer another organization\'s private types', function () {
    $theirs = CredentialType::factory()->privateTo(Organization::factory()->create())->create();
    $ours = CredentialType::factory()->privateTo($this->organization)->create();
    $this->actingAs($this->admin);

    $component = Livewire::test(HolderCredentials::class, ['holder' => $this->employee]);

    expect($component->get('availableTypes')->modelKeys())
        ->toContain($ours->id, $this->permitType->id)
        ->not->toContain($theirs->id);
});

it('renews a credential and keeps the old one as history', function () {
    $old = Credential::factory()->heldBy($this->employee)->for($this->permitType, 'type')->expiresInDays(5)->create();
    $this->actingAs($this->admin);

    Livewire::test(HolderCredentials::class, ['holder' => $this->employee])
        ->call('startRenew', $old->id)
        ->assertSet('mode', 'renew')
        ->assertSet('issuedOn', today()->toDateString())
        ->assertSet('expiresOn', today()->addMonthsNoOverflow(24)->toDateString())
        ->set('number', 'RENEWED')
        ->call('save')
        ->assertHasNoErrors();

    expect($old->fresh()->superseded_at)->not->toBeNull()
        ->and($this->employee->currentCredentials()->sole()->number)->toBe('RENEWED')
        ->and($this->employee->credentials()->count())->toBe(2);
});

it('edits a credential', function () {
    $credential = Credential::factory()->heldBy($this->employee)->for($this->permitType, 'type')->create();
    $this->actingAs($this->admin);

    Livewire::test(HolderCredentials::class, ['holder' => $this->employee])
        ->call('startEdit', $credential->id)
        ->set('number', 'FIXED-TYPO')
        ->call('save')
        ->assertHasNoErrors();

    expect($credential->fresh()->number)->toBe('FIXED-TYPO')
        ->and(Credential::count())->toBe(1);
});

it('deletes a credential with its documents', function () {
    Storage::fake('local');
    $credential = Credential::factory()->heldBy($this->employee)->create();
    Storage::disk('local')->put('credentials/x/file.pdf', 'pdf');
    $credential->documents()->create(['organization_id' => $this->organization->id, 'path' => 'credentials/x/file.pdf', 'original_name' => 'file.pdf', 'mime_type' => 'application/pdf', 'size' => 3]);
    $this->actingAs($this->admin);

    Livewire::test(HolderCredentials::class, ['holder' => $this->employee])->call('delete', $credential->id);

    expect(Credential::count())->toBe(0);
    Storage::disk('local')->assertMissing('credentials/x/file.pdf');
});

it('lets managers view but not change organization permits', function () {
    $this->actingAs($this->manager);

    Livewire::test(HolderCredentials::class, ['holder' => $this->organization])
        ->assertOk()
        ->assertSet('canManage', false)
        ->call('startCreate')
        ->assertForbidden();
});

it('lets managers manage their own unit\'s permits but not other units', function () {
    $otherUnit = Unit::factory()->for($this->organization)->create();
    $this->actingAs($this->manager);

    Livewire::test(HolderCredentials::class, ['holder' => $this->unit])->assertSet('canManage', true);
    Livewire::test(HolderCredentials::class, ['holder' => $otherUnit])->assertForbidden();
});

it('hides employees outside a manager\'s units', function () {
    $stranger = Employee::factory()->for($this->organization)->create();
    $this->actingAs($this->manager);

    Livewire::test(HolderCredentials::class, ['holder' => $stranger])->assertForbidden();
    $this->get(route('employees.show', $stranger))->assertForbidden();
});

it('shows credentials on the employee, unit and organization pages', function () {
    Credential::factory()->heldBy($this->employee)->for($this->permitType, 'type')->create();
    $this->actingAs($this->admin);

    $this->get(route('employees.show', $this->employee))->assertOk()->assertSee('Server permit');
    $this->get(route('units.show', $this->unit))->assertOk()->assertSee('Unit permits');
    $this->get(route('organization.permits'))->assertOk()->assertSee('Licenses');
});
