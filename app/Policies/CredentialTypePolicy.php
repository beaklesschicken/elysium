<?php

namespace App\Policies;

use App\Models\CredentialType;
use App\Models\User;

/**
 * Everyone in an organization can browse the catalog; admins maintain the organization's
 * private types. The public catalog is read-only.
 */
class CredentialTypePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->currentRole() !== null;
    }

    public function create(User $user): bool
    {
        return $user->isOrganizationAdmin();
    }

    public function update(User $user, CredentialType $credentialType): bool
    {
        return ! $credentialType->isPublic()
            && (int) $credentialType->organization_id === (int) $user->current_organization_id
            && $user->isOrganizationAdmin();
    }

    public function delete(User $user, CredentialType $credentialType): bool
    {
        return $this->update($user, $credentialType) && ! $credentialType->credentials()->exists();
    }
}
