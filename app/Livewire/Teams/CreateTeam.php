<?php

namespace App\Livewire\Teams;

use App\Models\Team;
use Livewire\Component;

class CreateTeam extends Component

    {
        public $name = '';
     
        public $deacription = '';
     
        public function save()
        {
            Team::create(
                $this->only(['name', 'description'])
            );
            session()->flash('status', 'Team successfully created.');
     
            return $this->redirect('/teams');
        }
     
        public function render()
        {
            return view('livewire.teams.create-team');
        }
}
