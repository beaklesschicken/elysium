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
        <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" :placeholder="__('Search name or email')" />
        <flux:select wire:model.live="status">
            <flux:select.option value="all">{{ __('All statuses') }}</flux:select.option>
            @foreach ($statuses as $statusOption)
                <flux:select.option :value="$statusOption->value">{{ $statusOption->label() }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:select wire:model.live="unit">
            <flux:select.option value="">{{ __('All units') }}</flux:select.option>
            @foreach ($units as $unitOption)
                <flux:select.option wire:key="unit-filter-{{ $unitOption->id }}" :value="$unitOption->id">{{ $unitOption->name }}</flux:select.option>
            @endforeach
        </flux:select>
    </div>

    <div class="overflow-x-auto rounded-xl border border-zinc-200 dark:border-zinc-700">
        <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-700">
            <thead class="bg-zinc-50 text-left text-zinc-600 dark:bg-zinc-900 dark:text-zinc-300">
                <tr>
                    <th class="px-4 py-3 font-medium">{{ __('Name') }}</th>
                    <th class="px-4 py-3 font-medium">{{ __('Units') }}</th>
                    <th class="hidden px-4 py-3 font-medium md:table-cell">{{ __('Email') }}</th>
                    <th class="hidden px-4 py-3 font-medium md:table-cell">{{ __('Started') }}</th>
                    <th class="px-4 py-3 font-medium">{{ __('Status') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                @forelse ($employees as $employee)
                    <tr wire:key="employee-{{ $employee->id }}" class="hover:bg-zinc-50 dark:hover:bg-zinc-700/50">
                        <td class="px-4 py-3">
                            <a href="{{ route('employees.edit', $employee) }}" wire:navigate class="font-medium text-zinc-900 hover:underline dark:text-white">{{ $employee->name }}</a>
                        </td>
                        <td class="px-4 py-3 text-zinc-600 dark:text-zinc-300">{{ $employee->units->pluck('name')->implode(', ') ?: '—' }}</td>
                        <td class="hidden px-4 py-3 text-zinc-600 md:table-cell dark:text-zinc-300">{{ $employee->email ?? '—' }}</td>
                        <td class="hidden px-4 py-3 text-zinc-600 md:table-cell dark:text-zinc-300">{{ $employee->start_date?->format('M j, Y') ?? '—' }}</td>
                        <td class="px-4 py-3">
                            <flux:badge size="sm" :color="$employee->status === App\Enums\EmployeeStatus::Active ? 'green' : 'zinc'">{{ $employee->status->label() }}</flux:badge>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-10 text-center text-zinc-500 dark:text-zinc-400">{{ __('No employees match.') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $employees->links() }}
</div>
