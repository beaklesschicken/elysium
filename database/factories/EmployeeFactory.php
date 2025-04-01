<?php

namespace Database\Factories;

use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Employee>
 */
class EmployeeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'organization_id' => Organization::factory(),
            'start_date' => $this->faker->dateTimeBetween('-10 years','-3 weeks'),
            'birth_date' => $this->faker->dateTimeBetween('-50 years','-16 years'),
            'notes' => '',
        ];
    }
}
