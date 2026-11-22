<?php

namespace App\Http\Controllers;

use App\Enums\EmployeeStatus;
use App\Models\Employee;
use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $organization = $user->currentOrganization;
        $isAdmin = $user->isOrganizationAdmin();

        $unitCount = Unit::query()->visibleTo($user)->count();
        $employeeCount = Employee::query()->visibleTo($user)->where('status', EmployeeStatus::Active)->count();
        $memberCount = $isAdmin ? $organization->users()->count() : null;

        $setupSteps = $isAdmin ? [
            ['label' => __('Add your first unit'), 'done' => $unitCount > 0, 'route' => route('units.create')],
            ['label' => __('Invite a unit manager'), 'done' => $memberCount > 1 || $organization->invitations()->exists(), 'route' => route('organization.members')],
            ['label' => __('Add employees'), 'done' => $employeeCount > 0, 'route' => route('employees.create')],
        ] : [];

        return view('dashboard', [
            'organization' => $organization,
            'unitCount' => $unitCount,
            'employeeCount' => $employeeCount,
            'memberCount' => $memberCount,
            'setupSteps' => collect($setupSteps)->contains('done', false) ? $setupSteps : [],
        ]);
    }
}
