<?php

namespace App\Http\Controllers;

use App\Enums\CredentialStatus;
use App\Enums\EmployeeStatus;
use App\Models\Credential;
use App\Models\Employee;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $organization = $user->currentOrganization;
        $isAdmin = $user->isOrganizationAdmin();

        $units = Unit::query()->visibleTo($user)->orderBy('name')->get(['id', 'name']);
        $unitCount = $units->count();
        $employeeCount = Employee::query()->visibleTo($user)->where('status', EmployeeStatus::Active)->count();
        $memberCount = $isAdmin ? $organization->users()->count() : null;

        $credentials = fn () => Credential::query()->visibleTo($user)->current();
        $credentialCounts = [
            'expired' => $credentials()->expired()->count(),
            'expiring_30' => $credentials()->expiringWithin(30)->count(),
            'expiring_90' => $credentials()->expiringWithin(90)->count(),
        ];

        $setupSteps = $isAdmin ? [
            ['label' => __('Add your first unit'), 'done' => $unitCount > 0, 'route' => route('units.create')],
            ['label' => __('Invite a unit manager'), 'done' => $memberCount > 1 || $organization->invitations()->exists(), 'route' => route('organization.members')],
            ['label' => __('Add employees'), 'done' => $employeeCount > 0, 'route' => route('employees.create')],
            ['label' => __('Record a permit or certificate'), 'done' => $credentials()->exists(), 'route' => route('employees.index')],
        ] : [];

        return view('dashboard', [
            'organization' => $organization,
            'unitCount' => $unitCount,
            'employeeCount' => $employeeCount,
            'memberCount' => $memberCount,
            'credentialCounts' => $credentialCounts,
            'unitSummaries' => $this->unitSummaries($units, $credentials()),
            'setupSteps' => collect($setupSteps)->contains('done', false) ? $setupSteps : [],
        ]);
    }

    /**
     * Expired and soon-expiring counts per unit, for the unit's own permits and its employees.
     *
     * @param  Collection<int, Unit>  $units
     * @param  Builder<Credential>  $credentials
     * @return Collection<int, array{unit: Unit, expired: int, expiring: int}>
     */
    private function unitSummaries(Collection $units, $credentials): Collection
    {
        $needingAttention = $credentials
            ->whereNotNull('expires_on')
            ->whereDate('expires_on', '<=', today()->addDays(CredentialStatus::EXPIRING_SOON_DAYS))
            ->get(['id', 'holder_type', 'holder_id', 'expires_on']);

        $employeeUnitIds = Employee::query()
            ->whereIn('id', $needingAttention->where('holder_type', 'employee')->pluck('holder_id'))
            ->with('units:units.id')
            ->get()
            ->mapWithKeys(fn (Employee $employee) => [$employee->id => $employee->units->modelKeys()]);

        return $units
            ->map(function (Unit $unit) use ($needingAttention, $employeeUnitIds): array {
                $forUnit = $needingAttention->filter(fn (Credential $credential) => match ($credential->holder_type) {
                    'unit' => (int) $credential->holder_id === $unit->id,
                    'employee' => in_array($unit->id, $employeeUnitIds->get($credential->holder_id, []), true),
                    default => false,
                });

                return [
                    'unit' => $unit,
                    'expired' => $forUnit->filter(fn (Credential $credential) => $credential->status() === CredentialStatus::Expired)->count(),
                    'expiring' => $forUnit->filter(fn (Credential $credential) => $credential->status() === CredentialStatus::ExpiringSoon)->count(),
                ];
            })
            ->filter(fn (array $summary) => $summary['expired'] + $summary['expiring'] > 0)
            ->sortByDesc(fn (array $summary) => [$summary['expired'], $summary['expiring']])
            ->values();
    }
}
