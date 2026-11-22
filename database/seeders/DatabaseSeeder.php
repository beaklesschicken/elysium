<?php

namespace Database\Seeders;

use App\Enums\OrganizationRole;
use App\Models\Employee;
use App\Models\Organization;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Test User (e@e.com / password) owns "Elysium Hospitality" with three units,
     * each with a manager and employees. A second organization checks tenant isolation.
     */
    public function run(): void
    {
        $organization = Organization::factory()->create(['name' => 'Elysium Hospitality']);

        User::factory()->inOrganization($organization, OrganizationRole::Owner)->create([
            'name' => 'Test User',
            'email' => 'e@e.com',
        ]);

        Unit::factory(3)->for($organization)->create()->each(function (Unit $unit) use ($organization): void {
            $manager = User::factory()->inOrganization($organization, OrganizationRole::Manager)->create();
            $unit->managers()->attach($manager);

            $employees = Employee::factory(8)->for($organization)->create();
            $unit->employees()->attach($employees);
        });

        $otherOrganization = Organization::factory()->create(['name' => 'Other Hospitality Group']);
        User::factory()->inOrganization($otherOrganization, OrganizationRole::Owner)->create([
            'name' => 'Other Owner',
            'email' => 'other@example.com',
        ]);
        $otherUnit = Unit::factory()->for($otherOrganization)->create();
        $otherUnit->employees()->attach(Employee::factory(5)->for($otherOrganization)->create());
    }
}
