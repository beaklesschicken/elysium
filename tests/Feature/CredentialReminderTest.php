<?php

use App\Enums\OrganizationRole;
use App\Livewire\Organizations\EditOrganization;
use App\Mail\CredentialRemindersMail;
use App\Models\Credential;
use App\Models\CredentialReminder;
use App\Models\Employee;
use App\Models\Organization;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

beforeEach(function () {
    Mail::fake();
    $this->organization = Organization::factory()->create();
    $this->owner = User::factory()->inOrganization($this->organization, OrganizationRole::Owner)->create();
    $this->manager = User::factory()->inOrganization($this->organization, OrganizationRole::Manager)->create();
    $this->unit = Unit::factory()->for($this->organization)->create();
    $this->unit->managers()->attach($this->manager);
    $this->employee = Employee::factory()->for($this->organization)->create();
    $this->employee->units()->attach($this->unit);
    $this->credential = Credential::factory()->heldBy($this->employee)->expiresInDays(25)->create();
});

it('is off by default', function () {
    expect(Organization::factory()->create()->fresh()->credential_reminders_enabled)->toBeFalse();

    $this->artisan('credentials:send-reminders')->assertSuccessful();

    Mail::assertNothingSent();
    expect(CredentialReminder::count())->toBe(0);
});

it('can be turned on by an admin in organization details', function () {
    $this->actingAs($this->owner);

    Livewire::test(EditOrganization::class)
        ->assertSet('credentialRemindersEnabled', false)
        ->set('credentialRemindersEnabled', true)
        ->call('save')
        ->assertHasNoErrors();

    expect($this->organization->fresh()->credential_reminders_enabled)->toBeTrue();
});

it('emails owners and the unit manager once per threshold', function () {
    $this->organization->update(['credential_reminders_enabled' => true]);

    $this->artisan('credentials:send-reminders')->assertSuccessful();

    Mail::assertSent(CredentialRemindersMail::class, 2);
    Mail::assertSent(CredentialRemindersMail::class, fn (CredentialRemindersMail $mail) => $mail->hasTo($this->owner->email) && $mail->credentials->modelKeys() === [$this->credential->id]);
    Mail::assertSent(CredentialRemindersMail::class, fn (CredentialRemindersMail $mail) => $mail->hasTo($this->manager->email));
    expect(CredentialReminder::pluck('days_before')->sort()->values()->all())->toBe([30, 90]);

    $this->artisan('credentials:send-reminders');
    Mail::assertSent(CredentialRemindersMail::class, 2);

    $this->travel(20)->days();
    $this->artisan('credentials:send-reminders');
    Mail::assertSent(CredentialRemindersMail::class, 4);
    expect(CredentialReminder::pluck('days_before')->sort()->values()->all())->toBe([7, 30, 90]);
});

it('only tells managers about their own units', function () {
    $this->organization->update(['credential_reminders_enabled' => true]);
    $otherEmployee = Employee::factory()->for($this->organization)->create();
    Credential::factory()->heldBy($otherEmployee)->expired()->create();
    Credential::factory()->heldBy($this->organization)->expiresInDays(3)->create();

    $this->artisan('credentials:send-reminders');

    Mail::assertSent(CredentialRemindersMail::class, fn (CredentialRemindersMail $mail) => $mail->hasTo($this->owner->email) && $mail->credentials->count() === 3);
    Mail::assertSent(CredentialRemindersMail::class, fn (CredentialRemindersMail $mail) => $mail->hasTo($this->manager->email) && $mail->credentials->modelKeys() === [$this->credential->id]);
});

it('skips renewed, far-off and inactive employees\' credentials', function () {
    $this->organization->update(['credential_reminders_enabled' => true]);
    $this->credential->renew(['expires_on' => today()->addYears(2)]);
    Credential::factory()->heldBy(Employee::factory()->for($this->organization)->inactive()->create())->expired()->create();

    $this->artisan('credentials:send-reminders');

    Mail::assertNothingSent();
});

it('does not touch organizations that left reminders off', function () {
    $this->organization->update(['credential_reminders_enabled' => true]);
    $quiet = Organization::factory()->create();
    User::factory()->inOrganization($quiet)->create();
    Credential::factory()->heldBy(Employee::factory()->for($quiet)->create())->expired()->create();

    $this->artisan('credentials:send-reminders');

    Mail::assertSent(CredentialRemindersMail::class, fn (CredentialRemindersMail $mail) => $mail->organization->is($this->organization));
    Mail::assertNotSent(CredentialRemindersMail::class, fn (CredentialRemindersMail $mail) => $mail->organization->is($quiet));
});

it('is scheduled daily', function () {
    $this->artisan('schedule:list')->expectsOutputToContain('credentials:send-reminders');
});
