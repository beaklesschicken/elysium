<?php

namespace Database\Seeders;

use App\Enums\OrganizationRole;
use App\Models\Credential;
use App\Models\CredentialType;
use App\Models\Employee;
use App\Models\Organization;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
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

            $this->seedCredentials($unit, $employees);
        });

        $otherOrganization = Organization::factory()->create(['name' => 'Other Hospitality Group']);
        User::factory()->inOrganization($otherOrganization, OrganizationRole::Owner)->create([
            'name' => 'Other Owner',
            'email' => 'other@example.com',
        ]);
        $otherUnit = Unit::factory()->for($otherOrganization)->create();
        $otherUnit->employees()->attach(Employee::factory(5)->for($otherOrganization)->create());
    }

    /**
     * A mix of valid, soon-expiring and expired credentials so every status shows up.
     *
     * @param  Collection<int, Employee>  $employees
     */
    private function seedCredentials(Unit $unit, $employees): void
    {
        $serverPermit = CredentialType::firstWhere('name', 'Alcohol server permit');
        $foodHandler = CredentialType::firstWhere('name', 'Food handler certificate');
        $retailPermit = CredentialType::firstWhere('name', 'Retail alcohol permit');

        $expiries = [400, 200, 75, 20, 5, -3, -40, null];

        foreach ($employees->values() as $index => $employee) {
            $days = $expiries[$index % count($expiries)];
            $factory = Credential::factory()->heldBy($employee)->for($serverPermit, 'type');
            ($days === null ? $factory->withoutExpiry() : $factory->expiresInDays($days))->create();

            if ($index % 2 === 0) {
                Credential::factory()->heldBy($employee)->for($foodHandler, 'type')->expiresInDays(300)->create();
            }
        }

        Credential::factory()->heldBy($unit)->for($retailPermit, 'type')->expiresInDays(45)->create();
    }
}
