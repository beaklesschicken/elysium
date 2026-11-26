<?php

use App\Enums\CredentialStatus;
use App\Models\Credential;
use App\Models\CredentialType;
use App\Models\Employee;
use Illuminate\Support\Carbon;

it('works out the status from the expiration date', function (?int $daysFromToday, CredentialStatus $expected) {
    $credential = Credential::factory()->make([
        'expires_on' => $daysFromToday === null ? null : today()->addDays($daysFromToday),
    ]);

    expect($credential->status())->toBe($expected);
})->with([
    'no expiry' => [null, CredentialStatus::Valid],
    'far away' => [31, CredentialStatus::Valid],
    'within 30 days' => [30, CredentialStatus::ExpiringSoon],
    'today' => [0, CredentialStatus::ExpiringSoon],
    'yesterday' => [-1, CredentialStatus::Expired],
]);

it('filters by status in queries the same way', function () {
    $employee = Employee::factory()->create();
    $expired = Credential::factory()->heldBy($employee)->expired()->create();
    $soon = Credential::factory()->heldBy($employee)->expiresInDays(10)->create();
    $later = Credential::factory()->heldBy($employee)->expiresInDays(60)->create();
    $never = Credential::factory()->heldBy($employee)->withoutExpiry()->create();

    expect(Credential::withStatus(CredentialStatus::Expired)->pluck('id')->all())->toBe([$expired->id])
        ->and(Credential::withStatus(CredentialStatus::ExpiringSoon)->pluck('id')->all())->toBe([$soon->id])
        ->and(Credential::withStatus(CredentialStatus::Valid)->pluck('id')->sort()->values()->all())->toBe([$later->id, $never->id])
        ->and(Credential::expiringWithin(90)->pluck('id')->sort()->values()->all())->toBe([$soon->id, $later->id]);
});

it('suggests an expiry from the type\'s usual validity', function () {
    $type = CredentialType::factory()->make(['validity_months' => 36]);

    expect($type->defaultExpiryFrom(Carbon::parse('2025-01-31'))->toDateString())->toBe('2028-01-31')
        ->and($type->defaultExpiryFrom(null))->toBeNull()
        ->and(CredentialType::factory()->make(['validity_months' => null])->defaultExpiryFrom(today()))->toBeNull();
});

it('ends an age-limited permit on the holder\'s birthday', function () {
    $type = CredentialType::factory()->make(['validity_months' => null, 'expires_at_age' => 21]);
    $employee = Employee::factory()->make(['birth_date' => Carbon::parse('2006-03-15')]);

    expect($type->defaultExpiryFrom(Carbon::parse('2025-06-01'), $employee)->toDateString())->toBe('2027-03-15')
        ->and($type->defaultExpiryFrom(null, $employee)->toDateString())->toBe('2027-03-15')
        ->and($type->defaultExpiryFrom(today(), Employee::factory()->make(['birth_date' => null])))->toBeNull()
        ->and($type->defaultExpiryFrom(today()))->toBeNull();
});

it('uses whichever comes first when a type has both a validity and an age limit', function () {
    $type = CredentialType::factory()->make(['validity_months' => 24, 'expires_at_age' => 21]);
    $employee = Employee::factory()->make(['birth_date' => Carbon::parse('2006-03-15')]);

    expect($type->defaultExpiryFrom(Carbon::parse('2024-01-10'), $employee)->toDateString())->toBe('2026-01-10')
        ->and($type->defaultExpiryFrom(Carbon::parse('2026-01-10'), $employee)->toDateString())->toBe('2027-03-15');
});

it('keeps history when renewing', function () {
    $credential = Credential::factory()->expiresInDays(5)->create();

    $renewed = $credential->renew(['number' => 'NEW-1', 'expires_on' => today()->addYears(2)]);

    expect($credential->fresh()->isCurrent())->toBeFalse()
        ->and($renewed->isCurrent())->toBeTrue()
        ->and($renewed->holder->is($credential->holder))->toBeTrue()
        ->and($renewed->credential_type_id)->toBe($credential->credential_type_id)
        ->and(Credential::current()->pluck('id')->all())->toBe([$renewed->id]);
});

it('reports the most urgent status for a holder', function () {
    $employee = Employee::factory()->create();
    expect($employee->worstCredentialStatus())->toBeNull();

    Credential::factory()->heldBy($employee)->expiresInDays(200)->create();
    Credential::factory()->heldBy($employee)->expiresInDays(3)->create();
    Credential::factory()->heldBy($employee)->expired()->superseded()->create();

    expect($employee->load('currentCredentials')->worstCredentialStatus())->toBe(CredentialStatus::ExpiringSoon);
});
