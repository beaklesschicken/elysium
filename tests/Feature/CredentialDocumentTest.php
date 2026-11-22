<?php

use App\Enums\OrganizationRole;
use App\Models\Credential;
use App\Models\Employee;
use App\Models\Organization;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');
    $this->organization = Organization::factory()->create();
    $this->employee = Employee::factory()->for($this->organization)->create();
    $this->credential = Credential::factory()->heldBy($this->employee)->create();
    Storage::disk('local')->put('credentials/'.$this->organization->id.'/abc.pdf', '%PDF-1.4');
    $this->document = $this->credential->documents()->create([
        'organization_id' => $this->organization->id,
        'path' => 'credentials/'.$this->organization->id.'/abc.pdf',
        'original_name' => 'permit.pdf',
        'mime_type' => 'application/pdf',
        'size' => 8,
    ]);
});

it('shows documents to people who can see the credential', function () {
    $this->actingAs(User::factory()->inOrganization($this->organization)->create())
        ->get(route('credentials.documents.show', [$this->credential, $this->document]))
        ->assertOk()
        ->assertHeader('Content-Type', 'application/pdf')
        ->assertHeader('X-Content-Type-Options', 'nosniff');
});

it('hides documents from other organizations', function () {
    $this->actingAs(User::factory()->inOrganization()->create())
        ->get(route('credentials.documents.show', [$this->credential, $this->document]))
        ->assertNotFound();
});

it('hides documents from managers of other units', function () {
    $manager = User::factory()->inOrganization($this->organization, OrganizationRole::Manager)->create();
    Unit::factory()->for($this->organization)->create()->managers()->attach($manager);

    $this->actingAs($manager)
        ->get(route('credentials.documents.show', [$this->credential, $this->document]))
        ->assertForbidden();
});

it('rejects a document paired with the wrong credential', function () {
    $other = Credential::factory()->heldBy($this->employee)->create();

    $this->actingAs(User::factory()->inOrganization($this->organization)->create())
        ->get(route('credentials.documents.show', [$other, $this->document]))
        ->assertNotFound();
});

it('requires a login', function () {
    $this->get(route('credentials.documents.show', [$this->credential, $this->document]))->assertRedirect('/login');
});
