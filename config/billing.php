<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Trial
    |--------------------------------------------------------------------------
    |
    | New organizations get a generic trial (no card) with no plan limits.
    |
    */

    'trial_days' => 14,

    /*
    |--------------------------------------------------------------------------
    | Subscription
    |--------------------------------------------------------------------------
    |
    | One paid plan, billed per unit. The Stripe price IDs are per-unit prices;
    | the display prices (whole dollars per unit) are only shown in the app.
    |
    */

    'subscription_type' => 'default',

    'prices' => [
        'monthly' => env('STRIPE_PRICE_MONTHLY'),
        'annual' => env('STRIPE_PRICE_ANNUAL'),
    ],

    'display_prices' => [
        'monthly' => (int) env('BILLING_UNIT_PRICE_MONTHLY', 29),
        'annual' => (int) env('BILLING_UNIT_PRICE_ANNUAL', 290),
    ],

    /*
    |--------------------------------------------------------------------------
    | Free plan
    |--------------------------------------------------------------------------
    |
    | Limits for organizations without a trial or an active subscription.
    |
    */

    'free' => [
        'units' => 1,
        'employees' => 25,
    ],

];
