<?php

namespace Database\Factories;

use App\Enums\CredentialCategory;
use App\Enums\CredentialHolderType;
use App\Models\CredentialType;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CredentialType>
 */
class CredentialTypeFactory extends Factory
{
    /**
     * Define the model's default state. Public (no organization) and held by employees.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'organization_id' => null,
            'name' => fake()->unique()->randomElement(['Bartender', 'Server', 'Kitchen', 'Safety', 'Allergen', 'Security', 'Manager']).' '.fake()->randomElement(['certificate', 'permit', 'training']).' '.fake()->unique()->numberBetween(1, 99999),
            'category' => CredentialCategory::Certificate,
            'holder_type' => CredentialHolderType::Employee,
            'issuing_authority' => fake()->company(),
            'us_state' => 'IN',
            'validity_months' => 24,
            'info_link' => null,
            'description' => fake()->sentence(),
        ];
    }

    public function forUnits(): static
    {
        return $this->state(fn (array $attributes) => [
            'holder_type' => CredentialHolderType::Unit,
            'category' => CredentialCategory::License,
        ]);
    }

    public function forOrganizations(): static
    {
        return $this->state(fn (array $attributes) => [
            'holder_type' => CredentialHolderType::Organization,
            'category' => CredentialCategory::License,
        ]);
    }

    public function privateTo(Organization $organization): static
    {
        return $this->state(fn (array $attributes) => [
            'organization_id' => $organization->id,
        ]);
    }
}
