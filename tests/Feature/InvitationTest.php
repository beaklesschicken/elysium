<?php

use App\Enums\OrganizationRole;
use App\Livewire\Invitations\AcceptInvitation;
use App\Livewire\Organizations\Members;
use App\Mail\InvitationMail;
use App\Models\Invitation;
use App\Models\Organization;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

beforeEach(function () {
    $this->organization = Organization::factory()->create(['name' => 'Copper Kettle']);
    $this->owner = User::factory()->inOrganization($this->organization)->create(['name' => 'Olive Owner']);
    $this->unit = Unit::factory()->for($this->organization)->create(['name' => 'Kettle Taproom']);
});

it('emails an invitation and stores only a hash of the token', function () {
    Mail::fake();
    $this->actingAs($this->owner);

    Livewire::test(Members::class)
        ->set('email', 'New.Manager@Example.com')
        ->set('role', 'manager')
        ->set('unitId', (string) $this->unit->id)
        ->call('invite')
        ->assertHasNoErrors();

    $invitation = Invitation::sole();
    expect($invitation->email)->toBe('new.manager@example.com')
        ->and($invitation->role)->toBe(OrganizationRole::Manager)
        ->and($invitation->unit_id)->toBe($this->unit->id);

    Mail::assertSent(InvitationMail::class, function (InvitationMail $mail) use ($invitation): bool {
        return $mail->hasTo('new.manager@example.com')
            && $mail->hasSubject('Olive Owner invited you to Copper Kettle')
            && Invitation::hashToken($mail->token) === $invitation->token_hash
            && $mail->token !== $invitation->token_hash;
    });
});

it('requires a unit when inviting a manager', function () {
    Mail::fake();
    $this->actingAs($this->owner);

    Livewire::test(Members::class)
        ->set('email', 'someone@example.com')
        ->set('role', 'manager')
        ->call('invite')
        ->assertHasErrors(['unitId' => 'required']);

    Mail::assertNothingSent();
});

it('does not invite people already in the organization or as owner', function () {
    Mail::fake();
    $this->actingAs($this->owner);

    Livewire::test(Members::class)
        ->set('email', $this->owner->email)
        ->set('role', 'admin')
        ->call('invite')
        ->assertHasErrors('email');

    Livewire::test(Members::class)
        ->set('email', 'boss@example.com')
        ->set('role', 'owner')
        ->call('invite')
        ->assertHasErrors(['role' => 'in']);

    Mail::assertNothingSent();
});

it('replaces an earlier pending invitation for the same email', function () {
    Mail::fake();
    $this->actingAs($this->owner);

    foreach ([1, 2] as $attempt) {
        Livewire::test(Members::class)->set('email', 'twice@example.com')->set('role', 'admin')->call('invite');
    }

    expect(Invitation::count())->toBe(1);
});

it('signs up a new person from the invitation', function () {
    Invitation::factory()->for($this->organization)->withToken('secret-token')->create([
        'email' => 'newbie@example.com',
        'unit_id' => $this->unit->id,
    ]);

    $this->get(route('invitations.accept', 'secret-token'))->assertOk()->assertSee('Join Copper Kettle');

    Livewire::test(AcceptInvitation::class, ['token' => 'secret-token'])
        ->set('name', 'Nia Newbie')
        ->set('password', 'password')
        ->set('password_confirmation', 'password')
        ->call('register')
        ->assertHasNoErrors()
        ->assertRedirect(route('dashboard', absolute: false));

    $user = User::firstWhere('email', 'newbie@example.com');
    $this->assertAuthenticatedAs($user);

    expect($user->email_verified_at)->not->toBeNull()
        ->and($user->current_organization_id)->toBe($this->organization->id)
        ->and($user->roleIn($this->organization))->toBe(OrganizationRole::Manager)
        ->and($user->managesUnit($this->unit))->toBeTrue()
        ->and(Invitation::withoutGlobalScopes()->sole()->accepted_at)->not->toBeNull();
});

it('lets a signed-in person with a matching email accept', function () {
    $existing = User::factory()->inOrganization()->create(['email' => 'existing@example.com']);
    Invitation::factory()->for($this->organization)->withToken('join-us')->create([
        'email' => 'existing@example.com',
        'role' => OrganizationRole::Admin,
    ]);
    $this->actingAs($existing);

    Livewire::test(AcceptInvitation::class, ['token' => 'join-us'])
        ->call('accept')
        ->assertRedirect(route('dashboard', absolute: false));

    $existing->refresh();
    expect($existing->roleIn($this->organization))->toBe(OrganizationRole::Admin)
        ->and($existing->organizations()->count())->toBe(2)
        ->and($existing->current_organization_id)->toBe($this->organization->id);
});

it('does not lower the role of someone already in the organization', function () {
    $admin = User::factory()->inOrganization($this->organization, OrganizationRole::Admin)->create();
    Invitation::factory()->for($this->organization)->withToken('again')->create([
        'email' => $admin->email,
        'unit_id' => $this->unit->id,
    ]);
    $this->actingAs($admin);

    Livewire::test(AcceptInvitation::class, ['token' => 'again'])->call('accept');

    expect($admin->fresh()->roleIn($this->organization))->toBe(OrganizationRole::Admin)
        ->and($admin->managesUnit($this->unit))->toBeTrue();
});

it('refuses someone signed in with a different email', function () {
    Invitation::factory()->for($this->organization)->withToken('not-yours')->create(['email' => 'right@example.com']);
    $wrongPerson = User::factory()->inOrganization()->create(['email' => 'wrong@example.com']);
    $this->actingAs($wrongPerson);

    Livewire::test(AcceptInvitation::class, ['token' => 'not-yours'])
        ->assertSee('This invitation is for right@example.com')
        ->call('accept')
        ->assertForbidden();

    expect($wrongPerson->fresh()->belongsToOrganization($this->organization))->toBeFalse();
});

it('sends people who already have an account to log in first', function () {
    User::factory()->create(['email' => 'has-account@example.com']);
    Invitation::factory()->for($this->organization)->withToken('log-in-first')->create(['email' => 'has-account@example.com']);

    $this->get(route('invitations.accept', 'log-in-first'))->assertOk()->assertSee('Log in to accept');
    expect(session('url.intended'))->toBe(route('invitations.accept', 'log-in-first'));

    Livewire::test(AcceptInvitation::class, ['token' => 'log-in-first'])
        ->set('name', 'Hijacker')
        ->set('password', 'password')
        ->set('password_confirmation', 'password')
        ->call('register')
        ->assertForbidden();
});

it('rejects expired, used and unknown invitations', function (string $state) {
    $factory = Invitation::factory()->for($this->organization)->withToken('old-token');
    $state === 'unknown' ? null : $factory->{$state}()->create();

    $this->get(route('invitations.accept', 'old-token'))->assertOk()->assertSee('Invitation unavailable');

    Livewire::test(AcceptInvitation::class, ['token' => 'old-token'])
        ->set('name', 'Late Comer')
        ->set('password', 'password')
        ->set('password_confirmation', 'password')
        ->call('register')
        ->assertNotFound();
})->with(['expired', 'accepted', 'unknown']);

it('lets admins revoke a pending invitation', function () {
    $invitation = Invitation::factory()->for($this->organization)->create();
    $this->actingAs($this->owner);

    Livewire::test(Members::class)->call('revokeInvitation', $invitation->id);

    expect(Invitation::count())->toBe(0);
});
