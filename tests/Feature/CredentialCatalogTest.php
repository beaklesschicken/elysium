<?php

use App\Enums\OrganizationRole;
use App\Livewire\Credentials\Types;
use App\Models\Credential;
use App\Models\CredentialType;
use App\Models\Employee;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Livewire\Livewire;

beforeEach(function () {
    $this->organization = Organization::factory()->create();
    $this->admin = User::factory()->inOrganization($this->organization, OrganizationRole::Admin)->create();
});

it('ships a public catalog', function () {
    expect(CredentialType::whereNull('organization_id')->pluck('name'))
        ->toContain('Alcohol server permit', 'Retail alcohol permit', 'Business registration');
});

it('lets admins add a private type', function () {
    $this->actingAs($this->admin);

    Livewire::test(Types::class)
        ->call('startCreate')
        ->set('name', 'Allergen awareness')
        ->set('category', 'training')
        ->set('holderType', 'employee')
        ->set('usState', 'in')
        ->set('validityMonths', '12')
        ->call('save')
        ->assertHasNoErrors();

    $type = CredentialType::firstWhere('name', 'Allergen awareness');
    expect($type->organization_id)->toBe($this->organization->id)
        ->and($type->us_state)->toBe('IN')
        ->and($type->validity_months)->toBe(12);
});

it('validates new types', function (string $field, string $value, string $rule) {
    $this->actingAs($this->admin);

    Livewire::test(Types::class)
        ->set('name', 'Valid')
        ->set($field, $value)
        ->call('save')
        ->assertHasErrors([$field => $rule]);
})->with([
    'missing name' => ['name', '', 'required'],
    'bad link' => ['infoLink', 'javascript:alert(1)', 'url'],
    'bad months' => ['validityMonths', '0', 'min'],
]);

it('keeps the public catalog read-only', function () {
    $public = CredentialType::whereNull('organization_id')->first();
    $this->actingAs($this->admin);

    Livewire::test(Types::class)->call('startEdit', $public->id)->assertForbidden();
    Livewire::test(Types::class)->call('delete', $public->id)->assertForbidden();
});

it('hides other organizations\' private types', function () {
    $theirs = CredentialType::factory()->privateTo(Organization::factory()->create())->create(['name' => 'Their secret type']);
    $this->actingAs($this->admin);

    $this->get(route('credentials.types'))->assertOk()->assertDontSee('Their secret type');
    expect(fn () => Livewire::test(Types::class)->call('startEdit', $theirs->id))
        ->toThrow(ModelNotFoundException::class);
});

it('refuses to delete a type that is in use', function () {
    $type = CredentialType::factory()->privateTo($this->organization)->create();
    Credential::factory()->for($type, 'type')->heldBy(Employee::factory()->for($this->organization)->create())->create();
    $this->actingAs($this->admin);

    Livewire::test(Types::class)->call('delete', $type->id)->assertForbidden();
    expect($type->fresh())->not->toBeNull();
});

it('stops managers from changing the catalog', function () {
    $this->actingAs(User::factory()->inOrganization($this->organization, OrganizationRole::Manager)->create());

    $this->get(route('credentials.types'))->assertOk();
    Livewire::test(Types::class)->call('startCreate')->assertForbidden();
});
