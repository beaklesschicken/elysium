<?php

namespace App\Enums;

enum BillingInterval: string
{
    case Monthly = 'monthly';
    case Annual = 'annual';

    public function label(): string
    {
        return match ($this) {
            self::Monthly => 'Monthly',
            self::Annual => 'Annual',
        };
    }

    /**
     * Unit of time shown next to a price, e.g. "per unit / month".
     */
    public function period(): string
    {
        return match ($this) {
            self::Monthly => 'month',
            self::Annual => 'year',
        };
    }

    public function stripePriceId(): ?string
    {
        return config("billing.prices.{$this->value}");
    }

    /**
     * Display price per unit, in whole dollars.
     */
    public function unitPrice(): int
    {
        return (int) config("billing.display_prices.{$this->value}");
    }

    public static function fromStripePrice(?string $priceId): ?self
    {
        if ($priceId === null) {
            return null;
        }

        foreach (self::cases() as $interval) {
            if ($interval->stripePriceId() === $priceId) {
                return $interval;
            }
        }

        return null;
    }
}
