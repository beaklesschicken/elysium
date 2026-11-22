<div class="flex w-full max-w-2xl flex-col gap-6">
    <div>
        <flux:heading size="xl" level="1">{{ __('Organization details') }}</flux:heading>
        <flux:subheading>{{ __('Your business name and mailing address.') }}</flux:subheading>
    </div>

    <flux:card>
    <form wire:submit="save" class="flex flex-col gap-6">
        <flux:input wire:model="name" :label="__('Organization name')" required />

        <x-address-fields />

        <div>
            <flux:button type="submit" variant="primary">{{ __('Save') }}</flux:button>
        </div>
    </form>
    </flux:card>
</div>
