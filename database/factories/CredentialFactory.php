<?php

namespace Database\Factories;

use App\Models\Credential;
use App\Models\CredentialType;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;

/**
 * @extends Factory<Credential>
 */
class CredentialFactory extends Factory
{
    /**
     * Define the model's default state: a valid credential held by a new employee.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $issuedOn = fake()->dateTimeBetween('-18 months', '-1 month');

        return [
            'credential_type_id' => CredentialType::factory(),
            'holder_type' => 'employee',
            'holder_id' => Employee::factory(),
            'organization_id' => fn (array $attributes) => Employee::find($attributes['holder_id'])?->organization_id,
            'number' => fake()->bothify('??-######'),
            'issued_on' => $issuedOn,
            'expires_on' => now()->addMonths(fake()->numberBetween(3, 24))->toDateString(),
        ];
    }

    /**
     * Held by the given employee, unit or organization.
     */
    public function heldBy(Model $holder): static
    {
        return $this->state(fn (array $attributes) => [
            'holder_type' => $holder->getMorphClass(),
            'holder_id' => $holder->id,
            'organization_id' => $holder->getMorphClass() === 'organization' ? $holder->id : $holder->organization_id,
        ]);
    }

    public function expiresInDays(int $days): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_on' => today()->addDays($days)->toDateString(),
        ]);
    }

    public function expired(int $daysAgo = 10): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_on' => today()->subDays($daysAgo)->toDateString(),
        ]);
    }

    public function withoutExpiry(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_on' => null,
        ]);
    }

    public function superseded(): static
    {
        return $this->state(fn (array $attributes) => [
            'superseded_at' => now()->subDay(),
        ]);
    }
}
