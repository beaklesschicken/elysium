<?php

namespace App\Http\Controllers;

use App\Models\Team;
use App\Models\User;
use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class TeamController extends Controller
{
    public $team_id = 0;
    public function index(int $team_id = 0)
    {
        $team_list = Session::get('team_ids');
        $teams = Team::where('organization_id','LIKE',$team_list)->get();
        // dd(Team::where('organization_id','LIKE',Session::get('team_ids'))->with('employees','managers')->get());
        if($team_id <> 0)
        {
            // [ ] List number of credentials for each user/employee that are expiring within the next 30 days
            // [ ] List number of credentials for each user/employee that are expiring within the next 90 days
            $working_team = Team::findOrFail($team_id)->employees()->get();
            $working_team_managers = Team::findorfail($team_id)->managers()->get();
        } else {
            $working_team = 0;
            $working_team_managers = 0;
        }
        return view('teams.index',['teams'=>$teams,'working_team'=>$working_team,'team_id'=>$team_id,'working_team_managers' => $working_team_managers]);
    }
    public function create()
    {
        return view('teams.create');
    }
}
