<div class="flex w-full flex-col gap-6">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <flux:link :href="route('employees.index')" wire:navigate class="text-sm">&larr; {{ __('Employees') }}</flux:link>
            <div class="mt-2 flex items-center gap-3">
                <flux:heading size="xl" level="1">{{ $employee->name }}</flux:heading>
                <flux:badge size="sm" :color="$employee->status === App\Enums\EmployeeStatus::Active ? 'green' : 'zinc'">{{ $employee->status->label() }}</flux:badge>
            </div>
            <flux:subheading>
                {{ collect([$employee->email, $employee->phone, $employee->start_date ? __('Started :date', ['date' => $employee->start_date->format('M j, Y')]) : null])->filter()->implode(' · ') }}
            </flux:subheading>
            @if ($units->isNotEmpty())
                <div class="mt-2 flex flex-wrap gap-2">
                    @foreach ($units as $unit)
                        <a wire:key="employee-unit-{{ $unit->id }}" href="{{ route('units.show', $unit) }}" wire:navigate>
                            <flux:badge size="sm" icon="building-storefront">{{ $unit->name }}</flux:badge>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
        @can('update', $employee)
            <flux:button icon="pencil-square" :href="route('employees.edit', $employee)" wire:navigate>{{ __('Edit details') }}</flux:button>
        @endcan
    </div>

    <livewire:credentials.holder-credentials :holder="$employee" :heading="__('Permits, trainings & certificates')" :key="'employee-credentials-'.$employee->id" />

    @if ($employee->notes)
        <flux:card>
            <flux:heading>{{ __('Notes') }}</flux:heading>
            <flux:text class="mt-2 whitespace-pre-line">{{ $employee->notes }}</flux:text>
        </flux:card>
    @endif
</div>
