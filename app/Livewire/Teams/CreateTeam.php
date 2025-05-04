<?php

namespace App\Livewire\Teams;

use App\Models\Team;
use App\Models\User;
use Livewire\Component;
use Livewire\Attributes\Validate;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Usernotnull\Toast\Concerns\WireToast;

class CreateTeam extends Component
{
    #[Validate('required|string|min:5')]
    public $name;
    #[Validate('required|string')]
    public $description;
    public $manager;
    public $users;
    public $organization_id;
    
    public function mount()
    {
    // list all the users available to be a manager for this team
        $this->users = User::where('organization_id',Session::get('working_org')->id)->get();
        $this->organization_id = Session::get('working_org')->id;
        $this->manager = Auth::user()->id; // set the selected manager to the user first (flux not working with default selection 04/03/25)
    }
    public function save()
    {
        // TODO attach user to newly created team

        $this->validate();
        $newTeam = Team::create(
            $this->all()
        );

        User::findOrFail($this->manager)->teams()->attach($newTeam->id);
        session()->flash('status', '<b>Team successfully created.</b><br />Test');
    
        return $this->redirect('/teams', navigate:true);
    }
    
    public function render()
    {
        return view('livewire.teams.create-team');
    }
}
