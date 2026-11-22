<div class="flex w-full flex-col gap-6">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <flux:link :href="route('credentials.index')" wire:navigate class="text-sm">&larr; {{ __('Credentials') }}</flux:link>
            <flux:heading size="xl" level="1" class="mt-2">{{ __('Credential catalog') }}</flux:heading>
            <flux:subheading>{{ __('The kinds of permits, trainings and certificates you can record. Add your own for anything missing.') }}</flux:subheading>
        </div>
        @can('create', App\Models\CredentialType::class)
            <flux:button variant="primary" icon="plus" wire:click="startCreate">{{ __('New credential type') }}</flux:button>
        @endcan
    </div>

    <div class="grid gap-4 sm:grid-cols-3">
        <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" :placeholder="__('Search name or issuer')" clearable class="sm:col-span-2" />
        <flux:select wire:model.live="holder" variant="listbox" clearable :placeholder="__('Held by anyone')">
            @foreach ($holderTypes as $holderOption)
                <flux:select.option :value="$holderOption->value">{{ __('Held by :holder', ['holder' => Str::lower($holderOption->label())]) }}</flux:select.option>
            @endforeach
        </flux:select>
    </div>

    <flux:card>
        <flux:table bleed>
            <flux:table.columns>
                <flux:table.column>{{ __('Name') }}</flux:table.column>
                <flux:table.column>{{ __('Held by') }}</flux:table.column>
                <flux:table.column class="max-md:hidden">{{ __('Issuer') }}</flux:table.column>
                <flux:table.column class="max-md:hidden">{{ __('Valid for') }}</flux:table.column>
                <flux:table.column align="end">{{ __('In use') }}</flux:table.column>
                <flux:table.column align="end"><span class="sr-only">{{ __('Actions') }}</span></flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @forelse ($types as $type)
                    <flux:table.row :key="$type->id">
                        <flux:table.cell>
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="font-medium text-zinc-800 dark:text-white">{{ $type->name }}</span>
                                <flux:badge size="sm" :color="$type->isPublic() ? 'zinc' : 'blue'">{{ $type->isPublic() ? __('Standard') : __('Yours') }}</flux:badge>
                                @if ($type->us_state)
                                    <flux:badge size="sm">{{ $type->us_state }}</flux:badge>
                                @endif
                            </div>
                            <div class="mt-1 flex flex-wrap items-center gap-2 text-xs text-zinc-500 dark:text-zinc-400">
                                <span>{{ $type->category->label() }}</span>
                                @if ($type->info_link)
                                    <a href="{{ $type->info_link }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-0.5 underline">{{ __('More info') }}<flux:icon.arrow-top-right-on-square variant="micro" /></a>
                                @endif
                            </div>
                        </flux:table.cell>
                        <flux:table.cell>{{ $type->holder_type->label() }}</flux:table.cell>
                        <flux:table.cell class="max-md:hidden">{{ $type->issuing_authority ?? '—' }}</flux:table.cell>
                        <flux:table.cell class="max-md:hidden">{{ $type->validity_months ? trans_choice(':count month|:count months', $type->validity_months) : __('Varies') }}</flux:table.cell>
                        <flux:table.cell align="end">{{ $type->credentials_count }}</flux:table.cell>
                        <flux:table.cell align="end">
                            @can('update', $type)
                                <flux:dropdown align="end">
                                    <flux:button size="sm" variant="ghost" icon="ellipsis-horizontal" :aria-label="__('Actions for :name', ['name' => $type->name])" />
                                    <flux:menu>
                                        <flux:menu.item icon="pencil-square" wire:click="startEdit({{ $type->id }})">{{ __('Edit') }}</flux:menu.item>
                                        @can('delete', $type)
                                            <flux:modal.trigger :name="'delete-type-'.$type->id">
                                                <flux:menu.item icon="trash" variant="danger">{{ __('Delete') }}</flux:menu.item>
                                            </flux:modal.trigger>
                                        @endcan
                                    </flux:menu>
                                </flux:dropdown>

                                <flux:modal :name="'delete-type-'.$type->id" class="min-w-[22rem] text-start">
                                    <div class="flex flex-col gap-6">
                                        <flux:heading size="lg">{{ __('Delete :name?', ['name' => $type->name]) }}</flux:heading>
                                        <div class="flex gap-2">
                                            <flux:spacer />
                                            <flux:modal.close><flux:button variant="ghost">{{ __('Cancel') }}</flux:button></flux:modal.close>
                                            <flux:button variant="danger" wire:click="delete({{ $type->id }})">{{ __('Delete') }}</flux:button>
                                        </div>
                                    </div>
                                </flux:modal>
                            @endcan
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="6" class="py-10 text-center">{{ __('No credential types match.') }}</flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </flux:card>

    @can('create', App\Models\CredentialType::class)
        <flux:modal name="credential-type-form" class="w-full max-w-lg">
            <form wire:submit="save" class="flex flex-col gap-6">
                <flux:heading size="lg">{{ $editingId ? __('Edit credential type') : __('New credential type') }}</flux:heading>

                <flux:input wire:model="name" :label="__('Name')" required :placeholder="__('e.g. Allergen awareness training')" />

                <div class="grid gap-6 sm:grid-cols-2">
                    <flux:select wire:model="category" :label="__('Category')" variant="listbox">
                        @foreach ($categories as $categoryOption)
                            <flux:select.option :value="$categoryOption->value">{{ $categoryOption->label() }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:select wire:model="holderType" :label="__('Held by')" variant="listbox" :disabled="$editingHasCredentials">
                        @foreach ($holderTypes as $holderOption)
                            <flux:select.option :value="$holderOption->value">{{ $holderOption->label() }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>

                <div class="grid gap-6 sm:grid-cols-6">
                    <flux:input wire:model="issuingAuthority" :label="__('Issued by')" class="sm:col-span-4" />
                    <flux:input wire:model="usState" :label="__('State')" maxlength="2" placeholder="IN" class="sm:col-span-2" />
                </div>

                <flux:input wire:model="validityMonths" type="number" min="1" max="240" :label="__('Usually valid for (months)')" :description:trailing="__('Used to suggest an expiration date. Leave empty if it varies.')" />
                <flux:input wire:model="infoLink" type="url" :label="__('Info link')" placeholder="https://" />
                <flux:textarea wire:model="description" :label="__('Description')" rows="2" />

                <div class="flex gap-2">
                    <flux:spacer />
                    <flux:modal.close><flux:button variant="ghost">{{ __('Cancel') }}</flux:button></flux:modal.close>
                    <flux:button type="submit" variant="primary">{{ __('Save') }}</flux:button>
                </div>
            </form>
        </flux:modal>
    @endcan
</div>
