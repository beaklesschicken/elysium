<?php

namespace App\Http\Controllers;

use App\Http\Requests\StartCheckoutRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Laravel\Cashier\Checkout;

class BillingController extends Controller
{
    /**
     * Send the owner to Stripe Checkout for the per-unit plan.
     */
    public function checkout(StartCheckoutRequest $request): Checkout|RedirectResponse
    {
        $organization = $request->user()->currentOrganization;

        if ($organization->activeSubscription() !== null) {
            return redirect()->route('organization.billing');
        }

        $interval = $request->billingInterval();

        if (blank($interval->stripePriceId())) {
            return redirect()->route('organization.billing')
                ->withErrors(['interval' => __('Billing is not set up yet. Please try again later.')]);
        }

        $builder = $organization
            ->newSubscription(config('billing.subscription_type'), $interval->stripePriceId())
            ->quantity($organization->billableUnitCount());

        if ($organization->onGenericTrial() && $organization->trial_ends_at->greaterThan(now()->addDays(2))) {
            $builder->trialUntil($organization->trial_ends_at);
        }

        return $builder->checkout([
            'success_url' => route('organization.billing', ['checkout' => 'success']),
            'cancel_url' => route('organization.billing'),
        ]);
    }

    /**
     * Send the owner to the Stripe customer portal (cards, invoices, interval, cancel).
     */
    public function portal(Request $request): RedirectResponse
    {
        $organization = $request->user()->currentOrganization;

        Gate::authorize('manageBilling', $organization);

        if (! $organization->hasStripeId()) {
            return redirect()->route('organization.billing');
        }

        return $organization->redirectToBillingPortal(route('organization.billing'));
    }
}
