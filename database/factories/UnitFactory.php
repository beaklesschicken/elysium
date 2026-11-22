<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Unit>
 */
class UnitFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'name' => fake()->randomElement(['The', 'Old', 'Copper', 'Union']).' '.fake()->lastName().' '.fake()->randomElement(['Tavern', 'Grill', 'Taproom', 'Kitchen', 'Bistro']),
            'description' => fake()->sentence(),
            'address' => fake()->streetAddress(),
            'city' => fake()->city(),
            'state_province' => 'IN',
            'postcode' => fake()->numerify('46###'),
            'phone' => fake()->numerify('317-555-####'),
        ];
    }
}
