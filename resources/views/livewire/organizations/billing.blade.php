<div class="flex w-full max-w-3xl flex-col gap-6">
    <div>
        <flux:heading size="xl" level="1">{{ __('Billing') }}</flux:heading>
        <flux:subheading>{{ __('Your plan, priced per unit. Only owners can see this page.') }}</flux:subheading>
    </div>

    @if ($checkoutResult === 'success' && ! $subscription)
        <flux:callout icon="clock" color="sky">
            <flux:callout.heading>{{ __('Thanks! Your subscription is being activated.') }}</flux:callout.heading>
            <flux:callout.text>{{ __('Stripe is confirming the payment. Refresh this page in a moment.') }}</flux:callout.text>
        </flux:callout>
    @endif

    @error('interval')
        <flux:callout icon="exclamation-triangle" color="red" :heading="$message" />
    @enderror

    <flux:card class="flex flex-col gap-4">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <flux:heading size="lg">{{ __('Current plan') }}</flux:heading>
            <flux:badge :color="match ($plan) { App\Enums\Plan::Standard => 'green', App\Enums\Plan::Trial => 'sky', App\Enums\Plan::Free => 'zinc' }">
                {{ $plan->label() }}
            </flux:badge>
        </div>

        @if ($subscription)
            <flux:text>
                {{ __(':units billed :interval.', [
                    'units' => trans_choice(':count unit|:count units', $subscription->quantity ?? $unitCount),
                    'interval' => strtolower($currentInterval?->label() ?? __('per period')),
                ]) }}
                @if ($subscription->onTrial())
                    {{ __('First charge on :date.', ['date' => $subscription->trial_ends_at->toFormattedDateString()]) }}
                @endif
                @if ($subscription->onGracePeriod())
                    {{ __('Cancelled; access continues until :date.', ['date' => $subscription->ends_at->toFormattedDateString()]) }}
                @endif
            </flux:text>
            <flux:text>{{ __('Adding or removing units updates your subscription automatically.') }}</flux:text>
            <div>
                <flux:button variant="primary" icon="credit-card" :href="route('billing.portal')">{{ __('Manage billing') }}</flux:button>
            </div>
            <flux:text size="sm">{{ __('Update your card, download invoices, switch between monthly and annual, or cancel.') }}</flux:text>
        @elseif ($plan === App\Enums\Plan::Trial)
            <flux:text>
                {{ __('Your free trial ends on :date. Everything is unlimited until then.', ['date' => $organization->trial_ends_at->toFormattedDateString()]) }}
            </flux:text>
            <flux:text>
                {{ __('After the trial, organizations without a subscription keep their data but are limited to :units and :employees.', [
                    'units' => trans_choice(':count unit|:count units', $freeUnitLimit),
                    'employees' => trans_choice(':count active employee|:count active employees', $freeEmployeeLimit),
                ]) }}
            </flux:text>
        @else
            <flux:text>
                {{ __('The Free plan includes :units and :employees. You have :unitCount and :employeeCount.', [
                    'units' => trans_choice(':count unit|:count units', $freeUnitLimit),
                    'employees' => trans_choice(':count active employee|:count active employees', $freeEmployeeLimit),
                    'unitCount' => trans_choice(':count unit|:count units', $organization->units()->count()),
                    'employeeCount' => trans_choice(':count active employee|:count active employees', $activeEmployeeCount),
                ]) }}
            </flux:text>
        @endif
    </flux:card>

    @unless ($subscription)
        <flux:card class="flex flex-col gap-5">
            <div>
                <flux:heading size="lg">{{ __('Standard') }}</flux:heading>
                <flux:text>{{ __('Unlimited units and employees, credential tracking and expiration reminders. You pay for each unit.') }}</flux:text>
            </div>

            <flux:radio.group wire:model.live="interval" variant="segmented" class="max-w-xs">
                @foreach ($intervals as $option)
                    <flux:radio wire:key="interval-{{ $option->value }}" :value="$option->value" :label="$option->label()" />
                @endforeach
            </flux:radio.group>

            <div class="flex flex-col gap-1">
                <div class="flex items-baseline gap-2">
                    <flux:heading size="xl">${{ number_format($selectedInterval->unitPrice()) }}</flux:heading>
                    <flux:text>{{ __('per unit / :period', ['period' => $selectedInterval->period()]) }}</flux:text>
                </div>
                <flux:text size="sm">
                    {{ __(':units today: $:total / :period.', [
                        'units' => trans_choice(':count unit|:count units', $unitCount),
                        'total' => number_format($estimatedTotal),
                        'period' => $selectedInterval->period(),
                    ]) }}
                    @if ($plan === App\Enums\Plan::Trial)
                        {{ __('You won\'t be charged until your trial ends.') }}
                    @endif
                </flux:text>
            </div>

            <form method="POST" action="{{ route('billing.checkout') }}">
                @csrf
                <input type="hidden" name="interval" value="{{ $selectedInterval->value }}" />
                <flux:button type="submit" variant="primary" icon-trailing="arrow-right">{{ __('Subscribe') }}</flux:button>
            </form>
        </flux:card>

        @if ($organization->hasStripeId())
            <div>
                <flux:button variant="ghost" size="sm" icon="document-text" :href="route('billing.portal')">{{ __('Past invoices') }}</flux:button>
            </div>
        @endif
    @endunless
</div>
