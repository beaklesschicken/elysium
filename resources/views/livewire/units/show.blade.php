<div class="flex w-full flex-col gap-6">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <flux:link :href="route('units.index')" wire:navigate class="text-sm">&larr; {{ __('Units') }}</flux:link>
            <flux:heading size="xl" level="1" class="mt-2">{{ $unit->name }}</flux:heading>
            <flux:subheading>
                {{ collect([$unit->description, $unit->address, trim($unit->city.' '.$unit->state_province.' '.$unit->postcode), $unit->phone])->filter()->implode(' · ') }}
            </flux:subheading>
        </div>
        @can('update', $unit)
            <flux:button icon="pencil-square" :href="route('units.edit', $unit)" wire:navigate>{{ __('Edit') }}</flux:button>
        @endcan
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <flux:card class="flex flex-col gap-4">
            <flux:card.header>
                <flux:card.heading size="lg">{{ __('Managers') }}</flux:card.heading>
            </flux:card.header>

            <ul class="flex flex-col divide-y divide-zinc-200 dark:divide-zinc-700">
                @forelse ($this->managers as $manager)
                    <li wire:key="manager-{{ $manager->id }}" class="flex items-center justify-between gap-2 py-2">
                        <div>
                            <div class="text-sm font-medium text-zinc-900 dark:text-white">{{ $manager->name }}</div>
                            <flux:text size="sm">{{ $manager->email }}</flux:text>
                        </div>
                        @can('update', $unit)
                            <flux:button size="sm" variant="ghost" icon="x-mark" wire:click="removeManager({{ $manager->id }})" wire:confirm="{{ __('Remove :name as a manager of this unit?', ['name' => $manager->name]) }}" :aria-label="__('Remove')" />
                        @endcan
                    </li>
                @empty
                    <li class="py-2"><flux:text>{{ __('No manager yet.') }}</flux:text></li>
                @endforelse
            </ul>

            @can('update', $unit)
                @if ($this->availableManagers->isNotEmpty())
                    <form wire:submit="addManager" class="flex items-end gap-2 [&>[data-flux-field]]:flex-1">
                        <flux:select wire:model="managerToAdd" :label="__('Add a manager')" variant="listbox" searchable :placeholder="__('Choose a person...')">
                            @foreach ($this->availableManagers as $person)
                                <flux:select.option wire:key="available-manager-{{ $person->id }}" :value="(string) $person->id" :description="$person->email">{{ $person->name }}</flux:select.option>
                            @endforeach
                        </flux:select>
                        <flux:button type="submit">{{ __('Add') }}</flux:button>
                    </form>
                @endif
                <flux:text size="sm">
                    {{ __('Managers need a login.') }}
                    <flux:link :href="route('organization.members')" wire:navigate>{{ __('Invite someone') }}</flux:link>
                </flux:text>
            @endcan
        </flux:card>

        <flux:card class="flex flex-col gap-4 lg:col-span-2">
            <flux:card.header>
                <flux:card.heading size="lg">{{ __('Employees') }} <span class="text-zinc-400">({{ $this->employees->count() }})</span></flux:card.heading>
                @can('create', App\Models\Employee::class)
                    <flux:card.actions>
                        <flux:button size="sm" icon="plus" :href="route('employees.create', ['unit' => $unit->id])" wire:navigate>{{ __('New employee') }}</flux:button>
                    </flux:card.actions>
                @endcan
            </flux:card.header>

            <ul class="flex flex-col divide-y divide-zinc-200 dark:divide-zinc-700">
                @forelse ($this->employees as $employee)
                    <li wire:key="employee-{{ $employee->id }}" class="flex items-center justify-between gap-2 py-2">
                        <div class="flex items-center gap-2">
                            <a href="{{ route('employees.edit', $employee) }}" wire:navigate class="text-sm font-medium text-zinc-900 hover:underline dark:text-white">{{ $employee->name }}</a>
                            @if ($employee->status !== App\Enums\EmployeeStatus::Active)
                                <flux:badge size="sm">{{ $employee->status->label() }}</flux:badge>
                            @endif
                        </div>
                        <flux:button size="sm" variant="ghost" icon="x-mark" wire:click="removeEmployee({{ $employee->id }})" wire:confirm="{{ __('Take :name off this unit?', ['name' => $employee->name]) }}" :aria-label="__('Remove')" />
                    </li>
                @empty
                    <li class="py-2"><flux:text>{{ __('No employees on this unit yet.') }}</flux:text></li>
                @endforelse
            </ul>

            @if ($this->availableEmployees->isNotEmpty())
                <form wire:submit="addEmployee" class="flex items-end gap-2 [&>[data-flux-field]]:flex-1">
                    <flux:select wire:model="employeeToAdd" :label="__('Add an existing employee')" variant="listbox" searchable :placeholder="__('Choose an employee...')">
                        @foreach ($this->availableEmployees as $candidate)
                            <flux:select.option wire:key="available-employee-{{ $candidate->id }}" :value="(string) $candidate->id" :keywords="$candidate->email">{{ $candidate->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:button type="submit">{{ __('Add') }}</flux:button>
                </form>
            @endif
        </flux:card>
    </div>
</div>
