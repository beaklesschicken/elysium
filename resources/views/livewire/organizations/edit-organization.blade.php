<div class="flex w-full max-w-2xl flex-col gap-6">
    <div>
        <flux:heading size="xl" level="1">{{ __('Organization details') }}</flux:heading>
        <flux:subheading>{{ __('Your business name and mailing address.') }}</flux:subheading>
    </div>

    <form wire:submit="save" class="flex flex-col gap-6">
        <flux:input wire:model="name" :label="__('Organization name')" required />

        <x-address-fields />

        <div class="flex items-center gap-4">
            <flux:button type="submit" variant="primary">{{ __('Save') }}</flux:button>
            <x-action-message on="organization-saved">{{ __('Saved.') }}</x-action-message>
        </div>
    </form>
</div>
