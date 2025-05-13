<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Credential>
 */
class CredentialFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    

    public function definition(): array
    {
        $types = ['Certification','Permit','License','Credential'];
        $cred = array_rand($types,1);
        $credType = $types[$cred];
        return [
            'name' => $this->faker->jobTitle()." ".$credType,
            'type' => $credType,
            'description' => $this->faker->sentence(),
            'us_state' => $this->faker->stateAbbr(),
            'info_link' => $this->faker->url(),
            

        ];
    }
}
