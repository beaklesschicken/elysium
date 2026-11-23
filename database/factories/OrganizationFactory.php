<?php

namespace Database\Factories;

use App\Enums\BillingInterval;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Organization>
 */
class OrganizationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->company(),
            'address' => $this->faker->streetAddress(),
            'city' => $this->faker->city(),
            'state_province' => 'IN',
            'postcode' => $this->faker->postcode(),
        ];
    }

    /**
     * Trial over and no subscription: the Free plan limits apply.
     */
    public function onFreePlan(): static
    {
        return $this->state(fn (): array => ['trial_ends_at' => now()->subDay()]);
    }

    /**
     * An active per-unit subscription, as Cashier stores it after the Stripe webhook.
     */
    public function subscribed(BillingInterval $interval = BillingInterval::Monthly, int $quantity = 1, string $status = 'active'): static
    {
        return $this->onFreePlan()
            ->state(fn (): array => ['stripe_id' => 'cus_'.Str::random(14)])
            ->afterCreating(function (Organization $organization) use ($interval, $quantity, $status): void {
                $subscription = $organization->subscriptions()->create([
                    'type' => config('billing.subscription_type'),
                    'stripe_id' => 'sub_'.Str::random(14),
                    'stripe_status' => $status,
                    'stripe_price' => $interval->stripePriceId() ?? 'price_'.$interval->value,
                    'quantity' => $quantity,
                ]);

                $subscription->items()->create([
                    'stripe_id' => 'si_'.Str::random(14),
                    'stripe_product' => 'prod_elysium',
                    'stripe_price' => $subscription->stripe_price,
                    'quantity' => $quantity,
                ]);
            });
    }
}
