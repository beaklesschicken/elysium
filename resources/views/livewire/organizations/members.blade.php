<div class="flex w-full flex-col gap-6">
    <div>
        <flux:heading size="xl" level="1">{{ __('People & access') }}</flux:heading>
        <flux:subheading>{{ __('Who can log in. Owners and admins manage everything; unit managers see only their units. Anyone who also needs their own credentials tracked can be added to the employee list.') }}</flux:subheading>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="flex flex-col gap-6 lg:col-span-2">
            @error('members')
                <flux:callout icon="exclamation-triangle" color="red" :heading="$message" />
            @enderror

            <flux:card>
                <flux:table bleed>
                    <flux:table.columns>
                        <flux:table.column>{{ __('Name') }}</flux:table.column>
                        <flux:table.column>{{ __('Role') }}</flux:table.column>
                        <flux:table.column class="max-md:hidden">{{ __('Manages') }}</flux:table.column>
                        <flux:table.column>{{ __('Own credentials') }}</flux:table.column>
                        <flux:table.column align="end"><span class="sr-only">{{ __('Actions') }}</span></flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @foreach ($members as $member)
                            @php($memberRole = App\Enums\OrganizationRole::from($member->pivot->role))
                            @php($roleLocked = $member->id === auth()->id() || ($memberRole === App\Enums\OrganizationRole::Owner && $actingRole !== App\Enums\OrganizationRole::Owner))
                            <flux:table.row :key="$member->id">
                                <flux:table.cell>
                                    <div class="flex items-center gap-3">
                                        <flux:avatar size="sm" :name="$member->name" color="auto" />
                                        <div>
                                            <div class="font-medium text-zinc-800 dark:text-white">{{ $member->name }}</div>
                                            <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ $member->email }}</div>
                                        </div>
                                    </div>
                                </flux:table.cell>
                                <flux:table.cell>
                                    @if ($roleLocked)
                                        <flux:badge size="sm">{{ $memberRole->label() }}</flux:badge>
                                    @else
                                        <flux:dropdown>
                                            <flux:button size="sm" variant="ghost" icon:trailing="chevron-down">{{ $memberRole->label() }}</flux:button>
                                            <flux:menu>
                                                @foreach ($roles as $roleOption)
                                                    @continue($roleOption === App\Enums\OrganizationRole::Owner && $actingRole !== App\Enums\OrganizationRole::Owner)
                                                    <flux:menu.item
                                                        wire:click="changeRole({{ $member->id }}, '{{ $roleOption->value }}')"
                                                        :icon="$roleOption === $memberRole ? 'check' : null"
                                                        :disabled="$roleOption === $memberRole">
                                                        {{ $roleOption->label() }}
                                                    </flux:menu.item>
                                                @endforeach
                                            </flux:menu>
                                        </flux:dropdown>
                                    @endif
                                </flux:table.cell>
                                <flux:table.cell class="max-md:hidden">
                                    {{ $member->managedUnits->pluck('name')->implode(', ') ?: '—' }}
                                </flux:table.cell>
                                <flux:table.cell>
                                    @if ($employeeIdsByUser->has($member->id))
                                        <flux:link :href="route('employees.show', $employeeIdsByUser->get($member->id))" wire:navigate class="text-sm">{{ __('View') }}</flux:link>
                                    @else
                                        <flux:tooltip :content="__('Add to the employee list so their own permits and certificates are tracked.')">
                                            <flux:button size="sm" variant="ghost" icon="plus" wire:click="trackAsEmployee({{ $member->id }})">{{ __('Track') }}</flux:button>
                                        </flux:tooltip>
                                    @endif
                                </flux:table.cell>
                                <flux:table.cell align="end">
                                    @if ($member->id !== auth()->id())
                                        <flux:modal.trigger :name="'remove-member-'.$member->id">
                                            <flux:button size="sm" variant="ghost" icon="trash" :aria-label="__('Remove')" />
                                        </flux:modal.trigger>

                                        <flux:modal :name="'remove-member-'.$member->id" class="min-w-[22rem] text-start">
                                            <div class="flex flex-col gap-6">
                                                <div>
                                                    <flux:heading size="lg">{{ __('Remove :name?', ['name' => $member->name]) }}</flux:heading>
                                                    <flux:text class="mt-2">{{ __('They lose access to this organization immediately. You can invite them again later.') }}</flux:text>
                                                </div>
                                                <div class="flex gap-2">
                                                    <flux:spacer />
                                                    <flux:modal.close>
                                                        <flux:button variant="ghost">{{ __('Cancel') }}</flux:button>
                                                    </flux:modal.close>
                                                    <flux:button variant="danger" wire:click="removeMember({{ $member->id }})" x-on:click="$flux.modal('remove-member-{{ $member->id }}').close()">{{ __('Remove') }}</flux:button>
                                                </div>
                                            </div>
                                        </flux:modal>
                                    @endif
                                </flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            </flux:card>

            @if ($invitations->isNotEmpty())
                <flux:card>
                    <flux:card.header>
                        <flux:card.heading size="lg">{{ __('Pending invitations') }}</flux:card.heading>
                    </flux:card.header>
                    <flux:table bleed>
                        <flux:table.rows>
                            @foreach ($invitations as $invitation)
                                <flux:table.row :key="$invitation->id">
                                    <flux:table.cell>
                                        <div class="font-medium text-zinc-800 dark:text-white">{{ $invitation->email }}</div>
                                        <div class="text-xs text-zinc-500 dark:text-zinc-400">
                                            {{ $invitation->role->label() }}{{ $invitation->unit ? ' · '.$invitation->unit->name : '' }}
                                            · {{ __('expires :date', ['date' => $invitation->expires_at->format('M j')]) }}
                                        </div>
                                    </flux:table.cell>
                                    <flux:table.cell align="end">
                                        <flux:button size="sm" variant="ghost" wire:click="revokeInvitation({{ $invitation->id }})">{{ __('Revoke') }}</flux:button>
                                    </flux:table.cell>
                                </flux:table.row>
                            @endforeach
                        </flux:table.rows>
                    </flux:table>
                </flux:card>
            @endif
        </div>

        <flux:card class="h-fit">
            <flux:card.header>
                <flux:card.heading size="lg">{{ __('Invite someone') }}</flux:card.heading>
            </flux:card.header>
            <form wire:submit="invite" class="flex flex-col gap-4">
                <flux:input wire:model="email" type="email" :label="__('Email')" required />

                <flux:radio.group wire:model.live="role" :label="__('Role')" variant="cards" class="flex-col">
                    @foreach ($invitableRoles as $roleOption)
                        <flux:radio
                            :value="$roleOption->value"
                            :label="$roleOption->label()"
                            :description="$roleOption === App\Enums\OrganizationRole::Admin ? __('Manages everything in the organization.') : __('Sees and manages their own unit.')" />
                    @endforeach
                </flux:radio.group>

                @if ($role === App\Enums\OrganizationRole::Manager->value)
                    @if ($units->isEmpty())
                        <flux:text>
                            {{ __('Add a unit first so the manager has something to manage.') }}
                            <flux:link :href="route('units.create')" wire:navigate>{{ __('New unit') }}</flux:link>
                        </flux:text>
                    @else
                        <flux:select wire:model="unitId" :label="__('Unit')" variant="listbox" searchable :placeholder="__('Choose a unit...')">
                            @foreach ($units as $unitOption)
                                <flux:select.option wire:key="invite-unit-{{ $unitOption->id }}" :value="(string) $unitOption->id">{{ $unitOption->name }}</flux:select.option>
                            @endforeach
                        </flux:select>
                    @endif
                @endif

                <div>
                    <flux:button type="submit" variant="primary" icon="envelope">{{ __('Send invitation') }}</flux:button>
                </div>
            </form>
        </flux:card>
    </div>
</div>
