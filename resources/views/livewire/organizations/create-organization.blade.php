<div class="flex flex-col gap-6">
    <x-auth-header :title="__('New organization')" :description="__('A business that owns restaurants, bars or venues. Its locations are added as units afterwards.')" />

    <form wire:submit="save" class="flex flex-col gap-6">
        <flux:input wire:model="name" :label="__('Organization name')" required autofocus />

        <x-address-fields />

        <flux:input wire:model="ownerEmail" type="email" :label="__('Owner\'s email')" :description:trailing="__('They get an invitation to set up their account. Leave empty to own it yourself.')" />

        <flux:button type="submit" variant="primary" class="w-full">
            {{ __('Create organization') }}
        </flux:button>
    </form>

    @if ($hasOrganizations)
        <div class="text-center text-sm">
            <flux:link :href="route('dashboard')" wire:navigate>{{ __('Back to dashboard') }}</flux:link>
        </div>
    @endif

    @persist('toast')
        <flux:toast />
    @endpersist
</div>
