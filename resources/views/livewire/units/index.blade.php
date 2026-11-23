<div class="flex w-full flex-col gap-6">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">{{ __('Units') }}</flux:heading>
            <flux:subheading>{{ __('Your locations, their managers and staff.') }}</flux:subheading>
        </div>
        @can('create', App\Models\Unit::class)
            <flux:button variant="primary" icon="plus" :href="route('units.create')" wire:navigate>{{ __('New unit') }}</flux:button>
        @endcan
    </div>

    <x-plan-limit-callout resource="units" />

    @if ($units->isEmpty())
        <flux:card class="text-center" variant="outline">
            <flux:heading>{{ __('No units yet') }}</flux:heading>
            <flux:text class="mt-1">
                @can('create', App\Models\Unit::class)
                    {{ __('Add each restaurant, bar or venue you run.') }}
                @else
                    {{ __('You have not been assigned to a unit yet.') }}
                @endcan
            </flux:text>
        </flux:card>
    @else
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            @foreach ($units as $unit)
                <a wire:key="unit-{{ $unit->id }}" href="{{ route('units.show', $unit) }}" wire:navigate class="group">
                    <flux:card class="flex h-full flex-col gap-2 transition group-hover:bg-zinc-50 dark:group-hover:bg-white/5">
                        <flux:heading size="lg">{{ $unit->name }}</flux:heading>
                        @if ($unit->city)
                            <flux:text>{{ $unit->city }}{{ $unit->state_province ? ', '.$unit->state_province : '' }}</flux:text>
                        @endif
                        <div class="mt-auto flex flex-wrap gap-2 pt-2">
                            <flux:badge size="sm" icon="users">{{ trans_choice(':count employee|:count employees', $unit->employees_count) }}</flux:badge>
                            @forelse ($unit->managers as $manager)
                                <flux:badge size="sm" color="blue" wire:key="unit-{{ $unit->id }}-manager-{{ $manager->id }}">{{ $manager->name }}</flux:badge>
                            @empty
                                <flux:badge size="sm" color="amber">{{ __('No manager') }}</flux:badge>
                            @endforelse
                        </div>
                    </flux:card>
                </a>
            @endforeach
        </div>
    @endif
</div>
