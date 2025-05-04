<?php

namespace App\Http\Controllers;

use App\Models\Team;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class DashboardController extends Controller
{
    public function index()
    {
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



        $users = User::with('teams')->get();
        return view('dashboard',['users' => $users]);
    }
}
