<?php

namespace App\Enums;

enum OrganizationRole: string
{
    case Owner = 'owner';
    case Admin = 'admin';
    case Manager = 'manager';

    public function label(): string
    {
        return match ($this) {
            self::Owner => 'Owner',
            self::Admin => 'Admin',
            self::Manager => 'Unit manager',
        };
    }

    /**
     * Owners and admins manage the whole organization; managers only their own units.
     */
    public function canManageOrganization(): bool
    {
        return $this !== self::Manager;
    }

    /**
     * Roles that can be handed out through an invitation.
     *
     * @return list<self>
     */
    public static function invitable(): array
    {
        return [self::Admin, self::Manager];
    }
}
