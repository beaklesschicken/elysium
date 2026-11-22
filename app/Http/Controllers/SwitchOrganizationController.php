<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class SwitchOrganizationController extends Controller
{
    public function __invoke(Request $request, Organization $organization): RedirectResponse
    {
        Gate::authorize('switchTo', $organization);

        $request->user()->switchOrganization($organization);

        return redirect()->route('dashboard');
    }
}
