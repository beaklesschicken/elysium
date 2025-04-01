<?php

namespace Database\Seeders;

use App\Models\Team;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\User;
use App\Models\Employee;
use App\Models\Organization;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'e@e.com',
        ]);

        // Make the first organization
        $org = Organization::factory()->create(['name' => 'Elysium Hospitality']);
        // make sure the example user is part of that organization
        $user = User::find(1)->organizations()->attach(1);
        // Create 4 more Users and attach them to different organizations
        User::factory(4)->hasAttached(Organization::factory())->create();
        // Create a team for each user in the db, and 5 employees for that team
        $users = User::all();
        foreach($users as $user) // creating and attaching
        {
            $team = Team::factory()->create([
                        'name' => $user->name ."'s Team",
                        'description' => "A brief description of ".$user->name."'s team.",
                        'organization_id' => 1,
                    ]);
            // Give the user a team
            $user->teams()->attach($team->id);
            // Give the team some employees
            $emps = Employee::factory(5)->recycle($team)->create([
                'organization_id' => $team->organization_id,
            ]);
            // Attach the employees we just created to the current team
            $team->employees()->attach($emps);

        }
    }
}
