<div class="flex w-full max-w-2xl flex-col gap-6">
    <div>
        <flux:link :href="route('employees.index')" wire:navigate class="text-sm">&larr; {{ __('Employees') }}</flux:link>
        <flux:heading size="xl" level="1" class="mt-2">{{ $employee ? $employee->name : __('New employee') }}</flux:heading>
    </div>

    <flux:card>
    <form wire:submit="save" class="flex flex-col gap-6">
        <flux:input wire:model="name" :label="__('Full name')" required autofocus />

        <div class="grid gap-6 sm:grid-cols-2">
            <flux:input wire:model="email" :label="__('Email')" type="email" />
            <flux:input wire:model="phone" :label="__('Phone')" type="tel" />
            <flux:date-picker wire:model="start_date" :label="__('Start date')" clearable with-today />
            <flux:date-picker wire:model="birth_date" :label="__('Birth date')" selectable-header clearable :description:trailing="__('Used for age-restricted service rules.')" />
        </div>

        <flux:radio.group wire:model="status" :label="__('Status')" variant="segmented" class="max-w-xs">
            @foreach ($statuses as $statusOption)
                <flux:radio :value="$statusOption->value" :label="$statusOption->label()" />
            @endforeach
        </flux:radio.group>

        @if ($this->assignableUnits->isNotEmpty())
            <flux:select wire:model="unitIds" :label="__('Units')" variant="listbox" multiple searchable indicator="checkbox" :placeholder="__('Choose units...')" :selected-suffix="__('units')">
                @foreach ($this->assignableUnits as $unitOption)
                    <flux:select.option wire:key="unit-option-{{ $unitOption->id }}" :value="(string) $unitOption->id">{{ $unitOption->name }}</flux:select.option>
                @endforeach
            </flux:select>
        @endif

        <flux:textarea wire:model="notes" :label="__('Notes')" rows="3" />

        <div class="flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center gap-4">
                <flux:button type="submit" variant="primary">{{ $employee ? __('Save') : __('Add employee') }}</flux:button>
                <flux:button variant="ghost" :href="$employee ? route('employees.show', $employee) : route('employees.index')" wire:navigate>{{ __('Cancel') }}</flux:button>
            </div>
            @if ($employee)
                @can('delete', $employee)
                    <flux:button variant="danger" icon="trash" wire:click="delete" wire:confirm="{{ __('Delete :name and their records? To keep history, set them to Inactive instead.', ['name' => $employee->name]) }}">{{ __('Delete') }}</flux:button>
                @endcan
            @endif
        </div>
    </form>
    </flux:card>
</div>
