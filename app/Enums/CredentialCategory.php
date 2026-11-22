<?php

namespace App\Enums;

enum CredentialCategory: string
{
    case AlcoholPermit = 'alcohol_permit';
    case FoodSafety = 'food_safety';
    case Training = 'training';
    case Certificate = 'certificate';
    case License = 'license';
    case Insurance = 'insurance';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::AlcoholPermit => 'Alcohol permit',
            self::FoodSafety => 'Food safety',
            self::Training => 'Training',
            self::Certificate => 'Certificate',
            self::License => 'License or permit',
            self::Insurance => 'Insurance',
            self::Other => 'Other',
        };
    }
}
