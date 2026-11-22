<?php

namespace App\Policies;

use App\Models\Employee;
use App\Models\User;

class EmployeePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->currentRole() !== null;
    }

    public function view(User $user, Employee $employee): bool
    {
        if ((int) $employee->organization_id !== (int) $user->current_organization_id) {
            return false;
        }

        return $user->isOrganizationAdmin()
            || Employee::query()->visibleTo($user)->whereKey($employee->id)->exists();
    }

    /**
     * Admins, and managers adding people to their own units.
     */
    public function create(User $user): bool
    {
        return $user->isOrganizationAdmin() || $user->managedUnits()->exists();
    }

    public function update(User $user, Employee $employee): bool
    {
        return $this->view($user, $employee);
    }

    public function delete(User $user, Employee $employee): bool
    {
        return (int) $employee->organization_id === (int) $user->current_organization_id && $user->isOrganizationAdmin();
    }
}
