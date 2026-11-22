<div class="flex w-full flex-col gap-6">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">{{ __('Employees') }}</flux:heading>
            <flux:subheading>{{ __('Everyone whose permits and certificates you track.') }}</flux:subheading>
        </div>
        @can('create', App\Models\Employee::class)
            <div class="flex gap-2">
                <flux:button icon="arrow-up-tray" :href="route('employees.import')" wire:navigate>{{ __('Import CSV') }}</flux:button>
                <flux:button variant="primary" icon="plus" :href="route('employees.create')" wire:navigate>{{ __('New employee') }}</flux:button>
            </div>
        @endcan
    </div>

    <div class="grid gap-4 sm:grid-cols-3">
        <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" :placeholder="__('Search name or email')" clearable />
        <flux:select wire:model.live="status" variant="listbox" :prefix="__('Status:')">
            <flux:select.option value="all">{{ __('All') }}</flux:select.option>
            @foreach ($statuses as $statusOption)
                <flux:select.option :value="$statusOption->value">{{ $statusOption->label() }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:select wire:model.live="unit" variant="listbox" searchable :placeholder="__('All units')" clearable>
            @foreach ($units as $unitOption)
                <flux:select.option wire:key="unit-filter-{{ $unitOption->id }}" :value="(string) $unitOption->id">{{ $unitOption->name }}</flux:select.option>
            @endforeach
        </flux:select>
    </div>

    <flux:card>
        <flux:table bleed :paginate="$employees" pagination:scroll-to>
            <flux:table.columns>
                <flux:table.column sortable :sorted="$sortBy === 'name'" :direction="$sortDirection" wire:click="sort('name')">{{ __('Name') }}</flux:table.column>
                <flux:table.column>{{ __('Credentials') }}</flux:table.column>
                <flux:table.column>{{ __('Units') }}</flux:table.column>
                <flux:table.column class="max-md:hidden">{{ __('Email') }}</flux:table.column>
                <flux:table.column sortable :sorted="$sortBy === 'start_date'" :direction="$sortDirection" wire:click="sort('start_date')" class="max-md:hidden">{{ __('Started') }}</flux:table.column>
                <flux:table.column sortable :sorted="$sortBy === 'status'" :direction="$sortDirection" wire:click="sort('status')">{{ __('Status') }}</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($employees as $employee)
                    <flux:table.row :key="$employee->id">
                        <flux:table.cell variant="strong">
                            <a href="{{ route('employees.show', $employee) }}" wire:navigate class="hover:underline">{{ $employee->name }}</a>
                        </flux:table.cell>
                        <flux:table.cell class="py-0"><x-credential-status-badge :status="$employee->worstCredentialStatus()" empty /></flux:table.cell>
                        <flux:table.cell>{{ $employee->units->pluck('name')->implode(', ') ?: '—' }}</flux:table.cell>
                        <flux:table.cell class="max-md:hidden">{{ $employee->email ?? '—' }}</flux:table.cell>
                        <flux:table.cell class="whitespace-nowrap max-md:hidden">{{ $employee->start_date?->format('M j, Y') ?? '—' }}</flux:table.cell>
                        <flux:table.cell class="py-0">
                            <flux:badge size="sm" :color="$employee->status === App\Enums\EmployeeStatus::Active ? 'green' : 'zinc'">{{ $employee->status->label() }}</flux:badge>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="6" class="py-10 text-center">{{ __('No employees match.') }}</flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </flux:card>
</div>
