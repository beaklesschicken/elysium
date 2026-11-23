<?php

namespace App\Policies;

use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\User;

class OrganizationPolicy
{
    /**
     * Only site admins create organizations while the portal is invite-only.
     */
    public function create(User $user): bool
    {
        return $user->isSiteAdmin();
    }

    public function switchTo(User $user, Organization $organization): bool
    {
        return $user->belongsToOrganization($organization);
    }

    /**
     * Edit details, invite and remove members, change roles.
     */
    public function manage(User $user, Organization $organization): bool
    {
        return $user->roleIn($organization)?->canManageOrganization() ?? false;
    }

    /**
     * See plans, subscribe, and open the Stripe customer portal.
     */
    public function manageBilling(User $user, Organization $organization): bool
    {
        return $user->roleIn($organization) === OrganizationRole::Owner;
    }
}
