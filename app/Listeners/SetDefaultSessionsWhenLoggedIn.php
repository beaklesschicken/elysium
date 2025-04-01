<?php

namespace App\Listeners;

use App\Models\Team;
use App\Events\UserLoggedIn;
use App\Livewire\Auth\Events\Login;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Auth\Events\Login as EventsLogin;

class SetDefaultSessionsWhenLoggedIn
{
    
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(EventsLogin $event): void
    {
        $user_orgs = Auth::User()->organizations; // all of the user's organizations
        // Get the info from the user's first organization
        $working_org = $user_orgs->first();
        // Now that the organization is set, get all the teams associated to this organiation
        $org_teams = Team::where('organization_id', $working_org->id)->get();
        // Get the ids into an array to check against
        $team_ids = [];
        foreach($org_teams as $team)
        {
            array_push($team_ids,$team->id); 
        }
        // Set the variables
        Session::put('working_org', $working_org);
        Session::put('org_teams', $org_teams);
        Session::put('team_ids',$team_ids);

    }
}
