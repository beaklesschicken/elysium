<?php

namespace App\Enums;

enum CredentialStatus: string
{
    case Expired = 'expired';
    case ExpiringSoon = 'expiring_soon';
    case Valid = 'valid';

    public const EXPIRING_SOON_DAYS = 30;

    public function label(): string
    {
        return match ($this) {
            self::Expired => 'Expired',
            self::ExpiringSoon => 'Expiring soon',
            self::Valid => 'Valid',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Expired => 'red',
            self::ExpiringSoon => 'amber',
            self::Valid => 'green',
        };
    }

    /**
     * Lower is more urgent.
     */
    public function urgency(): int
    {
        return match ($this) {
            self::Expired => 0,
            self::ExpiringSoon => 1,
            self::Valid => 2,
        };
    }
}
