<?php

namespace App\Policies;

use App\Models\Unit;
use App\Models\User;

class UnitPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->currentRole() !== null;
    }

    public function view(User $user, Unit $unit): bool
    {
        if (! $this->inCurrentOrganization($user, $unit)) {
            return false;
        }

        return $user->isOrganizationAdmin() || $user->managesUnit($unit);
    }

    public function create(User $user): bool
    {
        return $user->isOrganizationAdmin();
    }

    public function update(User $user, Unit $unit): bool
    {
        return $this->inCurrentOrganization($user, $unit) && $user->isOrganizationAdmin();
    }

    /**
     * Add or remove employees on the unit's roster.
     */
    public function manageRoster(User $user, Unit $unit): bool
    {
        return $this->view($user, $unit);
    }

    public function delete(User $user, Unit $unit): bool
    {
        return $this->update($user, $unit);
    }

    private function inCurrentOrganization(User $user, Unit $unit): bool
    {
        return (int) $unit->organization_id === (int) $user->current_organization_id;
    }
}
