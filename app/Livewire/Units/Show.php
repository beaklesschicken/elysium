<?php

namespace App\Livewire\Units;

use App\Models\Employee;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Unit')]
class Show extends Component
{
    public Unit $unit;

    public string $managerToAdd = '';

    public string $employeeToAdd = '';

    public function mount(Unit $unit): void
    {
        $this->authorize('view', $unit);
        $this->unit = $unit;
    }

    public function addManager(): void
    {
        $this->authorize('update', $this->unit);

        $this->validate([
            'managerToAdd' => ['required', Rule::in($this->availableManagers->modelKeys())],
        ], ['managerToAdd.in' => __('Pick someone from your organization.')]);

        $this->unit->managers()->syncWithoutDetaching([(int) $this->managerToAdd]);
        $this->reset('managerToAdd');
        $this->clearCaches();
    }

    public function removeManager(int $userId): void
    {
        $this->authorize('update', $this->unit);

        $this->unit->managers()->detach($userId);
        $this->clearCaches();
    }

    public function addEmployee(): void
    {
        $this->authorize('manageRoster', $this->unit);

        $this->validate([
            'employeeToAdd' => ['required', Rule::in($this->availableEmployees->modelKeys())],
        ], ['employeeToAdd.in' => __('Pick an employee from your organization.')]);

        $this->unit->employees()->syncWithoutDetaching([(int) $this->employeeToAdd]);
        $this->reset('employeeToAdd');
        $this->clearCaches();
    }

    public function removeEmployee(int $employeeId): void
    {
        $this->authorize('manageRoster', $this->unit);

        $this->unit->employees()->detach($employeeId);
        $this->clearCaches();
    }

    /**
     * @return Collection<int, User>
     */
    #[Computed]
    public function managers(): Collection
    {
        return $this->unit->managers()->orderBy('name')->get();
    }

    /**
     * @return Collection<int, Employee>
     */
    #[Computed]
    public function employees(): Collection
    {
        return $this->unit->employees()->orderBy('name')->get();
    }

    /**
     * Members of the organization who don't manage this unit yet.
     *
     * @return Collection<int, User>
     */
    #[Computed]
    public function availableManagers(): Collection
    {
        return Auth::user()->currentOrganization->users()
            ->whereNotIn('users.id', $this->managers->modelKeys())
            ->orderBy('name')
            ->get();
    }

    /**
     * Active employees the current user can see who aren't on this unit yet.
     *
     * @return Collection<int, Employee>
     */
    #[Computed]
    public function availableEmployees(): Collection
    {
        $user = Auth::user();

        return Employee::query()
            ->when(! $user->isOrganizationAdmin(), fn ($query) => $query->visibleTo($user))
            ->where('status', 'active')
            ->whereNotIn('id', $this->employees->modelKeys())
            ->orderBy('name')
            ->get();
    }

    private function clearCaches(): void
    {
        unset($this->managers, $this->employees, $this->availableManagers, $this->availableEmployees);
    }

    public function render(): View
    {
        return view('livewire.units.show');
    }
}
