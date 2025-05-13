<?php

namespace App\Livewire\Teams;

use App\Models\Team;
use Livewire\Component;
use App\Models\Organization;
use Illuminate\Support\Facades\Session;

class AddToTeam extends Component
{

    public $id;
    public $incoming = '';
    public $managers = [];
    public $employees = [];

    public function updatedIncoming()
    {
        $this->reset('managers','employees');

        if($this->incoming != '')
        {
            // TODO Ths will need to list all users (managers) and employees that work for this organization
            // Get all the results and iterate through the collection
            $teams = Team::where('organization_id',Session::get('working_org')->id)
            ->with(['managers','employees'])
            ->get();
            // get all the managers 
            $managers = [];
            $employees = [];
            foreach ($teams as $team )
            {
                foreach ( $team->managers as $manager )
                {
                    array_push($managers,
                    [
                    'user_id' => $manager->id,
                    'name' => $manager->name,
                    ]);
                }
                foreach($team->employees as $employee)
                {
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
        //
    }
    public function render()
    {
        return view('livewire.teams.add-to-team');
    }
}
