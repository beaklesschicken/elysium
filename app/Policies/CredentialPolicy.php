<?php

namespace App\Policies;

use App\Models\Credential;
use App\Models\Employee;
use App\Models\Organization;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;

/**
 * Access to a credential follows access to whoever holds it.
 */
class CredentialPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->currentRole() !== null;
    }

    public function view(User $user, Credential $credential): bool
    {
        return $this->inCurrentOrganization($user, $credential->organization_id)
            && $this->viewHolder($user, $credential->holder);
    }

    /**
     * Add, edit, renew or delete credentials (and their documents) for this holder.
     */
    public function manageFor(User $user, ?Model $holder): bool
    {
        return match (true) {
            $holder instanceof Employee => Gate::forUser($user)->allows('update', $holder),
            $holder instanceof Unit => Gate::forUser($user)->allows('manageRoster', $holder),
            $holder instanceof Organization => (int) $holder->id === (int) $user->current_organization_id && $user->isOrganizationAdmin(),
            default => false,
        };
    }

    public function update(User $user, Credential $credential): bool
    {
        return $this->inCurrentOrganization($user, $credential->organization_id)
            && $this->manageFor($user, $credential->holder);
    }

    public function delete(User $user, Credential $credential): bool
    {
        return $this->update($user, $credential);
    }

    public function viewHolder(User $user, ?Model $holder): bool
    {
        return match (true) {
            $holder instanceof Employee => Gate::forUser($user)->allows('view', $holder),
            $holder instanceof Unit => Gate::forUser($user)->allows('view', $holder),
            $holder instanceof Organization => (int) $holder->id === (int) $user->current_organization_id && $user->currentRole() !== null,
            default => false,
        };
    }

    private function inCurrentOrganization(User $user, int|string|null $organizationId): bool
    {
        return (int) $organizationId === (int) $user->current_organization_id;
    }
}
