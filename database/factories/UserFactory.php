<?php

namespace Database\Factories;

use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function siteAdmin(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_site_admin' => true,
        ]);
    }

    /**
     * Make the user a member of the organization (a new one by default) and select it.
     */
    public function inOrganization(?Organization $organization = null, OrganizationRole $role = OrganizationRole::Owner): static
    {
        return $this->afterCreating(function (User $user) use ($organization, $role): void {
            $organization ??= Organization::factory()->create();
            $organization->addMember($user, $role);
            $user->forceFill(['current_organization_id' => $organization->id])->save();
        });
    }
}
