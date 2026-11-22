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

    @if ($units->isEmpty())
        <div class="rounded-xl border border-dashed border-zinc-300 p-10 text-center dark:border-zinc-600">
            <flux:heading>{{ __('No units yet') }}</flux:heading>
            <flux:text class="mt-1">
                @can('create', App\Models\Unit::class)
                    {{ __('Add each restaurant, bar or venue you run.') }}
                @else
                    {{ __('You have not been assigned to a unit yet.') }}
                @endcan
            </flux:text>
        </div>
    @else
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            @foreach ($units as $unit)
                <a wire:key="unit-{{ $unit->id }}" href="{{ route('units.show', $unit) }}" wire:navigate
                    class="flex flex-col gap-2 rounded-xl border border-zinc-200 p-5 hover:bg-zinc-50 dark:border-zinc-700 dark:hover:bg-zinc-700/50">
                    <flux:heading size="lg">{{ $unit->name }}</flux:heading>
                    @if ($unit->city)
                        <flux:text>{{ $unit->city }}{{ $unit->state_province ? ', '.$unit->state_province : '' }}</flux:text>
                    @endif
                    <div class="mt-2 flex flex-wrap gap-2">
                        <flux:badge size="sm" icon="users">{{ trans_choice(':count employee|:count employees', $unit->employees_count) }}</flux:badge>
                        @forelse ($unit->managers as $manager)
                            <flux:badge size="sm" color="blue" wire:key="unit-{{ $unit->id }}-manager-{{ $manager->id }}">{{ $manager->name }}</flux:badge>
                        @empty
                            <flux:badge size="sm" color="amber">{{ __('No manager') }}</flux:badge>
                        @endforelse
                    </div>
                </a>
            @endforeach
        </div>
    @endif
</div>
