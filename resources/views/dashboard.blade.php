<x-layouts.app :title="__('Dashboard')">
    <div class="flex w-full flex-1 flex-col gap-6">
        <div>
            <flux:heading size="xl" level="1">{{ $organization->name }}</flux:heading>
            <flux:subheading>{{ __('Overview') }}</flux:subheading>
        </div>

        <div class="grid gap-4 sm:grid-cols-3">
            <a href="{{ route('units.index') }}" wire:navigate class="rounded-xl border border-zinc-200 p-5 hover:bg-zinc-50 dark:border-zinc-700 dark:hover:bg-zinc-700/50">
                <flux:text>{{ __('Units') }}</flux:text>
                <div class="mt-1 text-3xl font-semibold text-zinc-900 dark:text-white">{{ $unitCount }}</div>
            </a>
            <a href="{{ route('employees.index') }}" wire:navigate class="rounded-xl border border-zinc-200 p-5 hover:bg-zinc-50 dark:border-zinc-700 dark:hover:bg-zinc-700/50">
                <flux:text>{{ __('Active employees') }}</flux:text>
                <div class="mt-1 text-3xl font-semibold text-zinc-900 dark:text-white">{{ $employeeCount }}</div>
            </a>
            @if ($memberCount !== null)
                <a href="{{ route('organization.members') }}" wire:navigate class="rounded-xl border border-zinc-200 p-5 hover:bg-zinc-50 dark:border-zinc-700 dark:hover:bg-zinc-700/50">
                    <flux:text>{{ __('People with a login') }}</flux:text>
                    <div class="mt-1 text-3xl font-semibold text-zinc-900 dark:text-white">{{ $memberCount }}</div>
                </a>
            @endif
        </div>

        @if ($setupSteps)
            <div class="rounded-xl border border-zinc-200 p-5 dark:border-zinc-700">
                <flux:heading size="lg">{{ __('Get set up') }}</flux:heading>
                <ul class="mt-4 flex flex-col gap-3">
                    @foreach ($setupSteps as $step)
                        <li class="flex items-center gap-3">
                            <flux:icon :icon="$step['done'] ? 'check-circle' : 'plus-circle'" variant="mini" class="{{ $step['done'] ? 'text-green-600 dark:text-green-400' : 'text-zinc-400' }}" />
                            @if ($step['done'])
                                <flux:text class="line-through">{{ $step['label'] }}</flux:text>
                            @else
                                <flux:link :href="$step['route']" wire:navigate>{{ $step['label'] }}</flux:link>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        <flux:callout icon="shield-check" variant="secondary">
            <flux:callout.heading>{{ __('Credential tracking is coming next') }}</flux:callout.heading>
            <flux:callout.text>{{ __('Expiring and expired permits, trainings and certificates will show here.') }}</flux:callout.text>
        </flux:callout>
    </div>
</x-layouts.app>
