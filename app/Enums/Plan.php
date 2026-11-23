<?php

namespace App\Enums;

enum Plan: string
{
    case Free = 'free';
    case Trial = 'trial';
    case Standard = 'standard';

    public function label(): string
    {
        return match ($this) {
            self::Free => 'Free',
            self::Trial => 'Free trial',
            self::Standard => 'Standard',
        };
    }

    /**
     * Maximum number of units, or null for no limit.
     */
    public function unitLimit(): ?int
    {
        return $this === self::Free ? (int) config('billing.free.units') : null;
    }

    /**
     * Maximum number of active employees, or null for no limit.
     */
    public function employeeLimit(): ?int
    {
        return $this === self::Free ? (int) config('billing.free.employees') : null;
    }
}
