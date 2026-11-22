<?php

namespace App\Policies;

use App\Models\Organization;
use App\Models\User;

class OrganizationPolicy
{
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
}
