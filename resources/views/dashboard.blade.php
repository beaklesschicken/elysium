<x-layouts.app :title="__('Dashboard')">
    <div class="flex w-full flex-1 flex-col gap-6">
        <div>
            <flux:heading size="xl" level="1">{{ $organization->name }}</flux:heading>
            <flux:subheading>{{ __('Overview') }}</flux:subheading>
        </div>

        <div class="grid gap-4 sm:grid-cols-3">
            @foreach ([
                'expired' => ['label' => __('Expired credentials'), 'color' => 'text-red-600 dark:text-red-400'],
                'expiring_30' => ['label' => __('Expiring in 30 days'), 'color' => 'text-amber-600 dark:text-amber-400'],
                'expiring_90' => ['label' => __('Expiring in 90 days'), 'color' => 'text-zinc-900 dark:text-white'],
            ] as $window => $tile)
                <a href="{{ route('credentials.index', ['window' => $window]) }}" wire:navigate class="group">
                    <flux:card class="transition group-hover:bg-zinc-50 dark:group-hover:bg-white/5">
                        <flux:text>{{ $tile['label'] }}</flux:text>
                        <div class="mt-1 text-3xl font-semibold {{ $tile['color'] }}">{{ $credentialCounts[$window] }}</div>
                    </flux:card>
                </a>
            @endforeach
        </div>

        <div class="grid gap-6 lg:grid-cols-3">
            <div class="flex flex-col gap-6 lg:col-span-2">
                <flux:card>
                    <flux:card.header>
                        <flux:card.heading size="lg">{{ __('Units needing attention') }}</flux:card.heading>
                        <flux:card.actions>
                            <flux:button size="sm" variant="ghost" :href="route('credentials.index')" wire:navigate>{{ __('View all') }}</flux:button>
                        </flux:card.actions>
                    </flux:card.header>
                    @if ($unitSummaries->isEmpty())
                        <flux:text>{{ __('Nothing expired or expiring in the next 30 days.') }}</flux:text>
                    @else
                        <flux:table bleed>
                            <flux:table.columns>
                                <flux:table.column>{{ __('Unit') }}</flux:table.column>
                                <flux:table.column align="end">{{ __('Expired') }}</flux:table.column>
                                <flux:table.column align="end">{{ __('Expiring in 30 days') }}</flux:table.column>
                            </flux:table.columns>
                            <flux:table.rows>
                                @foreach ($unitSummaries as $summary)
                                    <flux:table.row :key="$summary['unit']->id">
                                        <flux:table.cell variant="strong">
                                            <a href="{{ route('credentials.index', ['unit' => $summary['unit']->id]) }}" wire:navigate class="hover:underline">{{ $summary['unit']->name }}</a>
                                        </flux:table.cell>
                                        <flux:table.cell align="end">
                                            @if ($summary['expired'])<flux:badge size="sm" color="red">{{ $summary['expired'] }}</flux:badge>@else 0 @endif
                                        </flux:table.cell>
                                        <flux:table.cell align="end">
                                            @if ($summary['expiring'])<flux:badge size="sm" color="amber">{{ $summary['expiring'] }}</flux:badge>@else 0 @endif
                                        </flux:table.cell>
                                    </flux:table.row>
                                @endforeach
                            </flux:table.rows>
                        </flux:table>
                    @endif
                </flux:card>

                @if ($setupSteps)
                    <flux:card>
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
                    </flux:card>
                @endif
            </div>

            <div class="flex flex-col gap-4">
                <a href="{{ route('units.index') }}" wire:navigate class="group">
                    <flux:card class="transition group-hover:bg-zinc-50 dark:group-hover:bg-white/5">
                        <flux:text>{{ __('Units') }}</flux:text>
                        <div class="mt-1 text-2xl font-semibold text-zinc-900 dark:text-white">{{ $unitCount }}</div>
                    </flux:card>
                </a>
                <a href="{{ route('employees.index') }}" wire:navigate class="group">
                    <flux:card class="transition group-hover:bg-zinc-50 dark:group-hover:bg-white/5">
                        <flux:text>{{ __('Active employees') }}</flux:text>
                        <div class="mt-1 text-2xl font-semibold text-zinc-900 dark:text-white">{{ $employeeCount }}</div>
                    </flux:card>
                </a>
                @if ($memberCount !== null)
                    <a href="{{ route('organization.members') }}" wire:navigate class="group">
                        <flux:card class="transition group-hover:bg-zinc-50 dark:group-hover:bg-white/5">
                            <flux:text>{{ __('People with a login') }}</flux:text>
                            <div class="mt-1 text-2xl font-semibold text-zinc-900 dark:text-white">{{ $memberCount }}</div>
                        </flux:card>
                    </a>
                @endif
            </div>
        </div>
    </div>
</x-layouts.app>
