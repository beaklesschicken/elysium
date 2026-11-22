<?php

use App\Enums\OrganizationRole;
use App\Livewire\Employees\Import;
use App\Models\Employee;
use App\Models\Organization;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;

beforeEach(function () {
    $this->organization = Organization::factory()->create();
    $this->admin = User::factory()->inOrganization($this->organization, OrganizationRole::Admin)->create();
    $this->taproom = Unit::factory()->for($this->organization)->create(['name' => 'Taproom']);
    $this->grill = Unit::factory()->for($this->organization)->create(['name' => 'Grill']);
});

function csvUpload(string $contents): UploadedFile
{
    return UploadedFile::fake()->createWithContent('employees.csv', $contents);
}

it('imports employees and matches units by name', function () {
    $this->actingAs($this->admin);

    Livewire::test(Import::class)
        ->set('file', csvUpload("\u{FEFF}Name,Email,Start Date,Unit\nAva Lager,ava@example.com,2024-05-01,taproom\nBen Stout,,03/15/2023,\n"))
        ->set('defaultUnit', (string) $this->grill->id)
        ->call('import')
        ->assertHasNoErrors()
        ->assertSet('importedCount', 2)
        ->assertSet('rowErrors', []);

    $ava = Employee::firstWhere('name', 'Ava Lager');
    $ben = Employee::firstWhere('name', 'Ben Stout');

    expect($ava->units()->pluck('name')->all())->toBe(['Taproom'])
        ->and($ava->start_date->toDateString())->toBe('2024-05-01')
        ->and($ben->units()->pluck('name')->all())->toBe(['Grill'])
        ->and($ben->organization_id)->toBe($this->organization->id);
});

it('imports nothing when any row has a problem', function () {
    $this->actingAs($this->admin);

    Livewire::test(Import::class)
        ->set('file', csvUpload("name,email,unit\nGood Row,good@example.com,Taproom\n,missing@example.com,\nBad Email,nope,\nLost,lost@example.com,Nowhere\n"))
        ->call('import')
        ->assertSet('importedCount', null)
        ->assertSet('rowErrors', fn (array $errors) => count($errors) === 3
            && str_contains($errors[0], 'Row 3')
            && str_contains($errors[1], 'Row 4')
            && str_contains($errors[2], 'Nowhere'));

    expect(Employee::count())->toBe(0);
});

it('requires a header with a name column', function () {
    $this->actingAs($this->admin);

    Livewire::test(Import::class)
        ->set('file', csvUpload("first,last\nAva,Lager\n"))
        ->call('import')
        ->assertHasErrors('file');
});

it('makes managers import onto their own units', function () {
    $manager = User::factory()->inOrganization($this->organization, OrganizationRole::Manager)->create();
    $this->taproom->managers()->attach($manager);
    $this->actingAs($manager);

    Livewire::test(Import::class)
        ->set('file', csvUpload("name,unit\nOn Grill,Grill\nNo Unit,\n"))
        ->call('import')
        ->assertSet('rowErrors', fn (array $errors) => count($errors) === 2);

    Livewire::test(Import::class)
        ->set('file', csvUpload("name\nOn Taproom\n"))
        ->set('defaultUnit', (string) $this->taproom->id)
        ->call('import')
        ->assertSet('importedCount', 1);
});

it('keeps people without units out of the importer', function () {
    $manager = User::factory()->inOrganization($this->organization, OrganizationRole::Manager)->create();

    $this->actingAs($manager)->get(route('employees.import'))->assertForbidden();
});
