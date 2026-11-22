<div class="flex w-full flex-col gap-6">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">{{ __('Credentials') }}</flux:heading>
            <flux:subheading>{{ __('Permits, trainings and certificates across your organization.') }}</flux:subheading>
        </div>
        <flux:button icon="book-open" :href="route('credentials.types')" wire:navigate>{{ __('Catalog') }}</flux:button>
    </div>

    <div class="grid gap-4 sm:grid-cols-3">
        @foreach ([
            'expired' => ['label' => __('Expired'), 'color' => 'text-red-600 dark:text-red-400'],
            'expiring_30' => ['label' => __('Expiring in 30 days'), 'color' => 'text-amber-600 dark:text-amber-400'],
            'expiring_90' => ['label' => __('Expiring in 90 days'), 'color' => 'text-zinc-900 dark:text-white'],
        ] as $key => $tile)
            <button type="button" wire:click="showWindow('{{ $key }}')" class="text-start" wire:key="tile-{{ $key }}">
                <flux:card class="transition hover:bg-zinc-50 dark:hover:bg-white/5 {{ $window === $key ? 'ring-2 ring-zinc-400 dark:ring-zinc-500' : '' }}">
                    <flux:text>{{ $tile['label'] }}</flux:text>
                    <div class="mt-1 text-3xl font-semibold {{ $tile['color'] }}">{{ $this->counts[$key] }}</div>
                </flux:card>
            </button>
        @endforeach
    </div>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-6">
        <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" :placeholder="__('Search name, credential or number')" clearable class="lg:col-span-2" />
        <flux:select wire:model.live="window" variant="listbox">
            @foreach ($windows as $value => $label)
                <flux:select.option :value="$value">{{ __($label) }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:select wire:model.live="unit" variant="listbox" searchable clearable :placeholder="__('All units')">
            @foreach ($units as $unitOption)
                <flux:select.option wire:key="credential-unit-{{ $unitOption->id }}" :value="(string) $unitOption->id">{{ $unitOption->name }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:select wire:model.live="holder" variant="listbox" clearable :placeholder="__('Held by anyone')">
            @foreach ($holderTypes as $holderType)
                <flux:select.option :value="$holderType->value">{{ $holderType->label() }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:select wire:model.live="category" variant="listbox" clearable :placeholder="__('Any category')">
            @foreach ($categories as $categoryOption)
                <flux:select.option :value="$categoryOption->value">{{ $categoryOption->label() }}</flux:select.option>
            @endforeach
        </flux:select>
    </div>

    <flux:card>
        <flux:table bleed :paginate="$credentials" pagination:scroll-to>
            <flux:table.columns>
                <flux:table.column>{{ __('Held by') }}</flux:table.column>
                <flux:table.column>{{ __('Credential') }}</flux:table.column>
                <flux:table.column class="max-md:hidden">{{ __('Number') }}</flux:table.column>
                <flux:table.column sortable sorted :direction="$sortDirection" wire:click="toggleSort">{{ __('Expires') }}</flux:table.column>
                <flux:table.column>{{ __('Status') }}</flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @forelse ($credentials as $credential)
                    @php($heldBy = $credential->holder)
                    <flux:table.row :key="$credential->id">
                        <flux:table.cell variant="strong">
                            @if ($heldBy instanceof App\Models\Employee)
                                <a href="{{ route('employees.show', $heldBy) }}" wire:navigate class="hover:underline">{{ $heldBy->name }}</a>
                            @elseif ($heldBy instanceof App\Models\Unit)
                                <a href="{{ route('units.show', $heldBy) }}" wire:navigate class="inline-flex items-center gap-1 hover:underline"><flux:icon.building-storefront variant="micro" />{{ $heldBy->name }}</a>
                            @elseif ($heldBy instanceof App\Models\Organization)
                                <a href="{{ route('organization.permits') }}" wire:navigate class="inline-flex items-center gap-1 hover:underline"><flux:icon.building-office variant="micro" />{{ $heldBy->name }}</a>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell>
                            <span class="inline-flex items-center gap-1">
                                {{ $credential->type->name }}
                                @if ($credential->documents->isNotEmpty())
                                    <flux:icon.paper-clip variant="micro" class="text-zinc-400" />
                                @endif
                            </span>
                        </flux:table.cell>
                        <flux:table.cell class="max-md:hidden">{{ $credential->number ?? '—' }}</flux:table.cell>
                        <flux:table.cell class="whitespace-nowrap">{{ $credential->expires_on?->format('M j, Y') ?? __('No expiration') }}</flux:table.cell>
                        <flux:table.cell class="py-0"><x-credential-status-badge :status="$credential->status()" /></flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="5" class="py-10 text-center">{{ __('Nothing here. Everything in this view is up to date.') }}</flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </flux:card>
</div>
