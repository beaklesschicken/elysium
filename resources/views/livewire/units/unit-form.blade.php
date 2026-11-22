<div class="flex w-full max-w-2xl flex-col gap-6">
    <div>
        <flux:heading size="xl" level="1">{{ $unit ? __('Edit :name', ['name' => $unit->name]) : __('New unit') }}</flux:heading>
        <flux:subheading>{{ __('A restaurant, bar, venue or other location.') }}</flux:subheading>
    </div>

    <form wire:submit="save" class="flex flex-col gap-6">
        <flux:input wire:model="name" :label="__('Name')" required autofocus />
        <flux:input wire:model="description" :label="__('Description')" :description="__('Optional, e.g. \'Rooftop bar\'.')" />
        <x-address-fields />
        <flux:input wire:model="phone" :label="__('Phone')" type="tel" autocomplete="tel" />

        <div class="flex items-center gap-4">
            <flux:button type="submit" variant="primary">{{ $unit ? __('Save') : __('Create unit') }}</flux:button>
            <flux:button variant="ghost" :href="$unit ? route('units.show', $unit) : route('units.index')" wire:navigate>{{ __('Cancel') }}</flux:button>
        </div>
    </form>
</div>
