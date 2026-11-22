<?php

use App\Enums\OrganizationRole;
use App\Livewire\Credentials\Index;
use App\Models\Credential;
use App\Models\CredentialType;
use App\Models\Employee;
use App\Models\Organization;
use App\Models\Unit;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->organization = Organization::factory()->create();
    $this->admin = User::factory()->inOrganization($this->organization, OrganizationRole::Admin)->create();
    $this->taproom = Unit::factory()->for($this->organization)->create(['name' => 'Taproom']);
    $this->grill = Unit::factory()->for($this->organization)->create(['name' => 'Grill']);

    $this->ava = Employee::factory()->for($this->organization)->create(['name' => 'Ava Taproom']);
    $this->ava->units()->attach($this->taproom);
    $this->ben = Employee::factory()->for($this->organization)->create(['name' => 'Ben Grill']);
    $this->ben->units()->attach($this->grill);

    $type = CredentialType::factory()->create(['name' => 'Server permit']);
    $this->avaExpired = Credential::factory()->heldBy($this->ava)->for($type, 'type')->expired()->create(['number' => 'AVA-1']);
    $this->benSoon = Credential::factory()->heldBy($this->ben)->for($type, 'type')->expiresInDays(20)->create(['number' => 'BEN-1']);
    $this->benLater = Credential::factory()->heldBy($this->ben)->for($type, 'type')->expiresInDays(300)->create(['number' => 'BEN-2']);
    $this->taproomPermit = Credential::factory()->heldBy($this->taproom)->for(CredentialType::factory()->forUnits(), 'type')->expiresInDays(60)->create(['number' => 'TAP-1']);
});

it('shows what needs attention by default', function () {
    $this->actingAs($this->admin);

    Livewire::test(Index::class)
        ->assertSee(['AVA-1', 'BEN-1', 'TAP-1'])
        ->assertDontSee('BEN-2')
        ->assertSet('counts', ['expired' => 1, 'expiring_30' => 1, 'expiring_90' => 2]);
});

it('filters by window, unit, holder and search', function () {
    $this->actingAs($this->admin);

    Livewire::test(Index::class)
        ->call('showWindow', 'expired')->assertSee('AVA-1')->assertDontSee('BEN-1')
        ->set('window', 'all')->assertSee('BEN-2')
        ->set('unit', (string) $this->taproom->id)->assertSee(['AVA-1', 'TAP-1'])->assertDontSee('BEN-')
        ->set('unit', '')->set('holder', 'unit')->assertSee('TAP-1')->assertDontSee('AVA-1')
        ->set('holder', '')->set('search', 'ben grill')->assertSee(['BEN-1', 'BEN-2'])->assertDontSee('AVA-1');
});

it('leaves out superseded credentials', function () {
    $this->avaExpired->renew(['expires_on' => today()->addYear()]);
    $this->actingAs($this->admin);

    Livewire::test(Index::class)->call('showWindow', 'expired')->assertDontSee('AVA-1');
});

it('shows managers only their units and employees', function () {
    $manager = User::factory()->inOrganization($this->organization, OrganizationRole::Manager)->create();
    $this->grill->managers()->attach($manager);
    $this->actingAs($manager);

    Livewire::test(Index::class)
        ->set('window', 'all')
        ->assertSee(['BEN-1', 'BEN-2'])
        ->assertDontSee(['AVA-1', 'TAP-1']);
});

it('shows counts and units needing attention on the dashboard', function () {
    $this->actingAs($this->admin)->get('/dashboard')
        ->assertOk()
        ->assertViewHas('credentialCounts', ['expired' => 1, 'expiring_30' => 1, 'expiring_90' => 2])
        ->assertViewHas('unitSummaries', fn ($summaries) => $summaries->pluck('unit.name')->all() === ['Taproom', 'Grill']
            && $summaries->first()['expired'] === 1
            && $summaries->last()['expiring'] === 1);
});
