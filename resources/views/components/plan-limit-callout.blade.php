@props(['resource'])

@php
    $limitUser = auth()->user();
    $limitOrganization = $limitUser?->currentOrganization;
    $limitReached = $limitOrganization && $limitUser->isOrganizationAdmin() && match ($resource) {
        'units' => ! $limitOrganization->canAddUnit(),
        'employees' => ! $limitOrganization->canAddEmployees(),
    };
@endphp

@if ($limitReached)
    <flux:callout icon="sparkles" color="amber" inline>
        <flux:callout.heading>
            {{ $resource === 'units'
                ? __('The Free plan includes :count unit.', ['count' => $limitOrganization->plan()->unitLimit()])
                : __('The Free plan includes :count active employees.', ['count' => $limitOrganization->plan()->employeeLimit()]) }}
        </flux:callout.heading>
        <flux:callout.text>
            @can('manageBilling', $limitOrganization)
                {{ __('Subscribe to add more. You pay per unit.') }}
            @else
                {{ __('Ask an owner of your organization to subscribe to add more.') }}
            @endcan
        </flux:callout.text>

        @can('manageBilling', $limitOrganization)
            <x-slot name="actions">
                <flux:button variant="primary" size="sm" :href="route('organization.billing')" wire:navigate>{{ __('See plans') }}</flux:button>
            </x-slot>
        @endcan
    </flux:callout>
@endif
