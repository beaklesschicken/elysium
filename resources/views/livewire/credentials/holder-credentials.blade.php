<div>
    <flux:card class="flex flex-col gap-4">
        <flux:card.header>
            <flux:card.heading size="lg">{{ $heading }}</flux:card.heading>
            @if ($this->canManage)
                <flux:card.actions>
                    <flux:button size="sm" icon="plus" wire:click="startCreate">{{ __('Add') }}</flux:button>
                </flux:card.actions>
            @endif
        </flux:card.header>

        @if ($this->credentials->isEmpty())
            <flux:text>{{ __('Nothing recorded yet.') }}</flux:text>
        @else
            <flux:table bleed>
                <flux:table.columns>
                    <flux:table.column>{{ __('Credential') }}</flux:table.column>
                    <flux:table.column class="max-md:hidden">{{ __('Issued') }}</flux:table.column>
                    <flux:table.column>{{ __('Expires') }}</flux:table.column>
                    <flux:table.column align="end"><span class="sr-only">{{ __('Actions') }}</span></flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @foreach ($this->credentials as $credential)
                        @php($status = $credential->status())
                        <flux:table.row :key="$credential->id">
                            <flux:table.cell>
                                <div class="flex flex-col gap-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="font-medium text-zinc-800 dark:text-white">{{ $credential->type->name }}</span>
                                        @if ($credential->verified_at)
                                            <flux:tooltip :content="__('Verified by :name on :date', ['name' => $credential->verifier?->name ?? __('a former member'), 'date' => $credential->verified_at->format('M j, Y')])">
                                                <flux:icon.check-badge variant="mini" class="text-blue-500" />
                                            </flux:tooltip>
                                        @endif
                                    </div>
                                    @if ($credential->number)
                                        <flux:text size="sm">#{{ $credential->number }}</flux:text>
                                    @endif
                                    @foreach ($credential->documents as $attachment)
                                        <div wire:key="document-{{ $attachment->id }}" class="flex items-center gap-1">
                                            <flux:link :href="route('credentials.documents.show', [$credential, $attachment])" target="_blank" class="flex items-center gap-1 text-sm">
                                                <flux:icon.paper-clip variant="micro" />{{ $attachment->original_name }}
                                            </flux:link>
                                            <flux:text size="sm">({{ $attachment->humanSize() }})</flux:text>
                                            @if ($this->canManage)
                                                <flux:button size="xs" variant="subtle" icon="x-mark" wire:click="deleteDocument({{ $attachment->id }})" wire:confirm="{{ __('Remove :file?', ['file' => $attachment->original_name]) }}" :aria-label="__('Remove :file', ['file' => $attachment->original_name])" />
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </flux:table.cell>
                            <flux:table.cell class="whitespace-nowrap max-md:hidden">{{ $credential->issued_on?->format('M j, Y') ?? '—' }}</flux:table.cell>
                            <flux:table.cell class="whitespace-nowrap">
                                <div class="flex flex-col items-start gap-1">
                                    <span>{{ $credential->expires_on?->format('M j, Y') ?? __('No expiration') }}</span>
                                    @if ($credential->expires_on)
                                        <flux:badge size="sm" :color="$status->color()">
                                            @if ($status === App\Enums\CredentialStatus::Expired)
                                                {{ trans_choice('Expired :count day ago|Expired :count days ago', abs($credential->daysUntilExpiry())) }}
                                            @elseif ($credential->daysUntilExpiry() === 0)
                                                {{ __('Expires today') }}
                                            @else
                                                {{ trans_choice(':count day left|:count days left', $credential->daysUntilExpiry()) }}
                                            @endif
                                        </flux:badge>
                                    @endif
                                </div>
                            </flux:table.cell>
                            <flux:table.cell align="end">
                                @if ($this->canManage)
                                    <flux:dropdown align="end">
                                        <flux:button size="sm" variant="ghost" icon="ellipsis-horizontal" :aria-label="__('Actions for :name', ['name' => $credential->type->name])" />
                                        <flux:menu>
                                            <flux:menu.item icon="arrow-path" wire:click="startRenew({{ $credential->id }})">{{ __('Renew') }}</flux:menu.item>
                                            <flux:menu.item icon="pencil-square" wire:click="startEdit({{ $credential->id }})">{{ __('Edit') }}</flux:menu.item>
                                            <flux:menu.separator />
                                            <flux:modal.trigger :name="'delete-credential-'.$credential->id">
                                                <flux:menu.item icon="trash" variant="danger">{{ __('Delete') }}</flux:menu.item>
                                            </flux:modal.trigger>
                                        </flux:menu>
                                    </flux:dropdown>

                                    <flux:modal :name="'delete-credential-'.$credential->id" class="min-w-[22rem] text-start">
                                        <div class="flex flex-col gap-6">
                                            <div>
                                                <flux:heading size="lg">{{ __('Delete :name?', ['name' => $credential->type->name]) }}</flux:heading>
                                                <flux:text class="mt-2">{{ __('This removes the record and its documents for good. To record a new card or certificate, use Renew instead so the history is kept.') }}</flux:text>
                                            </div>
                                            <div class="flex gap-2">
                                                <flux:spacer />
                                                <flux:modal.close>
                                                    <flux:button variant="ghost">{{ __('Cancel') }}</flux:button>
                                                </flux:modal.close>
                                                <flux:button variant="danger" wire:click="delete({{ $credential->id }})">{{ __('Delete') }}</flux:button>
                                            </div>
                                        </div>
                                    </flux:modal>
                                @endif
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        @endif

        @if ($this->history->isNotEmpty())
            <div>
                <flux:button size="sm" variant="ghost" :icon="$showHistory ? 'chevron-up' : 'chevron-down'" wire:click="$toggle('showHistory')">
                    {{ trans_choice(':count earlier record|:count earlier records', $this->history->count()) }}
                </flux:button>

                @if ($showHistory)
                    <ul class="mt-2 flex flex-col divide-y divide-zinc-100 text-sm dark:divide-white/10">
                        @foreach ($this->history as $old)
                            <li wire:key="history-{{ $old->id }}" class="flex flex-wrap items-center justify-between gap-2 py-2">
                                <div>
                                    <span class="text-zinc-700 dark:text-zinc-200">{{ $old->type->name }}</span>
                                    <span class="text-zinc-500 dark:text-zinc-400">
                                        {{ $old->number ? '#'.$old->number.' · ' : '' }}{{ $old->issued_on?->format('M j, Y') ?? '—' }} – {{ $old->expires_on?->format('M j, Y') ?? __('no expiration') }}
                                    </span>
                                </div>
                                <div class="flex items-center gap-2">
                                    @foreach ($old->documents as $attachment)
                                        <flux:link wire:key="history-document-{{ $attachment->id }}" :href="route('credentials.documents.show', [$old, $attachment])" target="_blank" class="text-sm">{{ $attachment->original_name }}</flux:link>
                                    @endforeach
                                    <flux:badge size="sm">{{ __('Replaced :date', ['date' => $old->superseded_at->format('M j, Y')]) }}</flux:badge>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        @endif
    </flux:card>

    @if ($this->canManage)
        <flux:modal :name="$this->modalName()" class="w-full max-w-lg" wire:close="$refresh">
            <form wire:submit="save" class="flex flex-col gap-6">
                <div>
                    <flux:heading size="lg">
                        @switch($mode)
                            @case('renew') {{ __('Renew credential') }} @break
                            @case('edit') {{ __('Edit credential') }} @break
                            @default {{ __('Add credential') }}
                        @endswitch
                    </flux:heading>
                    @if ($mode === 'renew')
                        <flux:text class="mt-2">{{ __('Enter the new card or certificate. The current one is kept in the history.') }}</flux:text>
                    @endif
                </div>

                @if ($this->availableTypes->isEmpty())
                    <flux:callout icon="information-circle" variant="secondary">
                        <flux:callout.text>
                            {{ __('There are no credential types for this yet.') }}
                            <flux:link :href="route('credentials.types')" wire:navigate>{{ __('Add one to the catalog') }}</flux:link>
                        </flux:callout.text>
                    </flux:callout>
                @else
                    <flux:select wire:model.live="credentialTypeId" :label="__('Credential')" variant="listbox" searchable :placeholder="__('Choose a credential...')" :disabled="$mode === 'renew'">
                        @foreach ($this->availableTypes as $type)
                            <flux:select.option
                                wire:key="type-option-{{ $type->id }}"
                                :value="(string) $type->id"
                                :description="collect([$type->issuing_authority, $type->us_state, $type->isPublic() ? null : __('Your organization')])->filter()->implode(' · ')">
                                {{ $type->name }}
                            </flux:select.option>
                        @endforeach
                    </flux:select>
                @endif

                <flux:input wire:model="number" :label="__('Number')" :placeholder="__('Permit or certificate number')" />

                <div class="grid gap-6 sm:grid-cols-2">
                    <flux:date-picker wire:model.live="issuedOn" :label="__('Issued')" clearable with-today />
                    <flux:date-picker wire:model="expiresOn" :label="__('Expires')" clearable :description:trailing="__('Leave empty if it never expires.')" />
                </div>

                <div class="flex flex-col gap-3">
                    <flux:file-upload wire:model="document" :label="__('Document')" accept=".pdf,.jpg,.jpeg,.png,.webp,.heic,application/pdf,image/*">
                        <flux:file-upload.dropzone :heading="__('Drop a scan or photo, or click to browse')" :text="__('PDF or image, up to 10 MB')" with-progress inline />
                    </flux:file-upload>
                    @if ($document)
                        <flux:file-item :heading="$document->getClientOriginalName()" :size="$document->getSize()">
                            <x-slot name="actions">
                                <flux:file-item.remove wire:click="$set('document', null)" :aria-label="__('Remove file')" />
                            </x-slot>
                        </flux:file-item>
                    @endif
                </div>

                <flux:textarea wire:model="notes" :label="__('Notes')" rows="2" />

                <flux:checkbox wire:model="verified" :label="__('I have seen the original')" :description="__('Marks the credential as verified by you.')" />

                <div class="flex gap-2">
                    <flux:spacer />
                    <flux:modal.close>
                        <flux:button variant="ghost">{{ __('Cancel') }}</flux:button>
                    </flux:modal.close>
                    <flux:button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="save,document">{{ __('Save') }}</flux:button>
                </div>
            </form>
        </flux:modal>
    @endif
</div>
