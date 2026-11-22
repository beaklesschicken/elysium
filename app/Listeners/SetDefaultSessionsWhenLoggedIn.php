<?php

namespace App\Listeners;

use App\Models\Team;
use Illuminate\Auth\Events\Login as EventsLogin;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

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

        if (! $working_org) {
            return;
        }

        // Now that the organization is set, get all the teams associated to this organiation
        $org_teams = Team::where('organization_id', $working_org->id)->get();
        // Get the ids into an array to check against
        $team_ids = [];
        foreach ($org_teams as $team) {
            array_push($team_ids, $team->id);
        }
        // Set the variables
        Session::put('working_org', $working_org);
        Session::put('org_teams', $org_teams);
        Session::put('team_ids', $team_ids);

    }
}
