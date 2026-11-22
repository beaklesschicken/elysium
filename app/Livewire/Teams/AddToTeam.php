<?php

namespace App\Livewire\Teams;

use App\Models\Organization;
use App\Models\Team;
use Illuminate\Support\Facades\Session;
use Livewire\Component;

class AddToTeam extends Component
{
    public $id;

    public $incoming = '';

    public $managers = [];

    public $employees = [];

    public $team_id = '';

    public function mount($team_id = 0)
    {
        $this->team_id = $team_id;
    }

    public function updatedIncoming()
    {
        $this->reset('managers', 'employees');

        if ($this->incoming != '') {
            // TODO Ths will need to list all users (managers) and employees that work for this organization
            // Get all the results and iterate through the collection
            $teams = Team::where('organization_id', Session::get('working_org')->id)
                ->with(['managers', 'employees'])
                ->get();
            // get all the managers
            $managers = [];
            $employees = [];
            foreach ($teams as $team) {
                foreach ($team->managers as $manager) {
                    array_push($managers,
                        [
                            'user_id' => $manager->id,
                            'name' => $manager->name,
                        ]);
                }
                foreach ($team->employees as $employee) {
                    array_push($employees,
                        [
                            'emp_id' => $employee->id,
                            'name' => $employee->name,
                        ]);
                }
            }

            // Send the results
            $this->managers = $managers;
            $this->employees = $employees;

        } else {
            $this->managers = [];
            $this->employees = [];
        }
    }

    public function changeIncoming()
    {
        // get the id of the incoming selected name and attach it to the team
        if ($this->incoming != '') {
            $team = Team::findOrFail($this->team_id);
            $user = Organization::findOrFail(Session::get('working_org')->id)->users()->where('name', $this->incoming)->first();
            if ($user) {
                // Check if the user is already a manager or employee
                if (! $team->managers()->where('user_id', $user->id)->exists()) {
                    $team->managers()->attach($user->id);
                }
                if (! $team->employees()->where('emp_id', $user->id)->exists()) {
                    $team->employees()->attach($user->id);
                }
            }
        }
        // Reset the incoming field
        $this->incoming = '';
    }

    public function render()
    {
        return view('livewire.teams.add-to-team');
    }
}
