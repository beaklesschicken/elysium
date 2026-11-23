<?php

namespace App\Jobs;

use App\Models\Organization;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Keeps the per-unit subscription quantity equal to the organization's unit count.
 * Safe to run more than once: it reads the count when it runs and skips Stripe if nothing changed.
 */
class SyncSubscriptionQuantity implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public int $organizationId) {}

    public function handle(): void
    {
        $organization = Organization::find($this->organizationId);
        $subscription = $organization?->activeSubscription();

        if ($subscription === null) {
            return;
        }

        $quantity = $organization->billableUnitCount();

        if ((int) $subscription->quantity !== $quantity) {
            $subscription->updateQuantity($quantity);
        }
    }
}
