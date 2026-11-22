<div class="flex w-full flex-col gap-6">
    <div>
        <flux:heading size="xl" level="1">{{ __('People & access') }}</flux:heading>
        <flux:subheading>{{ __('Who can log in. Owners and admins manage everything; unit managers see only their units.') }}</flux:subheading>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <section class="flex flex-col gap-4 lg:col-span-2">
            @error('members')
                <flux:callout icon="exclamation-triangle" color="red" :heading="$message" />
            @enderror

            <div class="overflow-x-auto rounded-xl border border-zinc-200 dark:border-zinc-700">
                <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-700">
                    <thead class="bg-zinc-50 text-left text-zinc-600 dark:bg-zinc-900 dark:text-zinc-300">
                        <tr>
                            <th class="px-4 py-3 font-medium">{{ __('Name') }}</th>
                            <th class="px-4 py-3 font-medium">{{ __('Role') }}</th>
                            <th class="hidden px-4 py-3 font-medium md:table-cell">{{ __('Manages') }}</th>
                            <th class="px-4 py-3"><span class="sr-only">{{ __('Actions') }}</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                        @foreach ($members as $member)
                            @php($memberRole = App\Enums\OrganizationRole::from($member->pivot->role))
                            <tr wire:key="member-{{ $member->id }}">
                                <td class="px-4 py-3">
                                    <div class="font-medium text-zinc-900 dark:text-white">{{ $member->name }}</div>
                                    <div class="text-zinc-500 dark:text-zinc-400">{{ $member->email }}</div>
                                </td>
                                <td class="px-4 py-3">
                                    @if ($member->id === auth()->id() || ($memberRole === App\Enums\OrganizationRole::Owner && $actingRole !== App\Enums\OrganizationRole::Owner))
                                        <flux:badge size="sm">{{ $memberRole->label() }}</flux:badge>
                                    @else
                                        <flux:select size="sm" wire:change="changeRole({{ $member->id }}, $event.target.value)" :aria-label="__('Role for :name', ['name' => $member->name])">
                                            @foreach ($roles as $roleOption)
                                                @continue($roleOption === App\Enums\OrganizationRole::Owner && $actingRole !== App\Enums\OrganizationRole::Owner)
                                                <flux:select.option :value="$roleOption->value" :selected="$roleOption === $memberRole">{{ $roleOption->label() }}</flux:select.option>
                                            @endforeach
                                        </flux:select>
                                    @endif
                                </td>
                                <td class="hidden px-4 py-3 text-zinc-600 md:table-cell dark:text-zinc-300">
                                    {{ $member->managedUnits->pluck('name')->implode(', ') ?: '—' }}
                                </td>
                                <td class="px-4 py-3 text-right">
                                    @if ($member->id !== auth()->id())
                                        <flux:button size="sm" variant="ghost" icon="trash" wire:click="removeMember({{ $member->id }})" wire:confirm="{{ __('Remove :name from the organization? They will lose access immediately.', ['name' => $member->name]) }}" :aria-label="__('Remove')" />
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($invitations->isNotEmpty())
                <flux:heading size="lg" class="mt-2">{{ __('Pending invitations') }}</flux:heading>
                <ul class="flex flex-col divide-y divide-zinc-200 rounded-xl border border-zinc-200 dark:divide-zinc-700 dark:border-zinc-700">
                    @foreach ($invitations as $invitation)
                        <li wire:key="invitation-{{ $invitation->id }}" class="flex items-center justify-between gap-4 px-4 py-3 text-sm">
                            <div>
                                <div class="font-medium text-zinc-900 dark:text-white">{{ $invitation->email }}</div>
                                <div class="text-zinc-500 dark:text-zinc-400">
                                    {{ $invitation->role->label() }}{{ $invitation->unit ? ' · '.$invitation->unit->name : '' }}
                                    · {{ __('expires :date', ['date' => $invitation->expires_at->format('M j')]) }}
                                </div>
                            </div>
                            <flux:button size="sm" variant="ghost" wire:click="revokeInvitation({{ $invitation->id }})">{{ __('Revoke') }}</flux:button>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>

        <section class="flex h-fit flex-col gap-4 rounded-xl border border-zinc-200 p-5 dark:border-zinc-700">
            <flux:heading size="lg">{{ __('Invite someone') }}</flux:heading>
            <form wire:submit="invite" class="flex flex-col gap-4">
                <flux:input wire:model="email" type="email" :label="__('Email')" required />
                <flux:select wire:model.live="role" :label="__('Role')">
                    @foreach ($invitableRoles as $roleOption)
                        <flux:select.option :value="$roleOption->value">{{ $roleOption->label() }}</flux:select.option>
                    @endforeach
                </flux:select>
                @if ($role === App\Enums\OrganizationRole::Manager->value)
                    @if ($units->isEmpty())
                        <flux:text>
                            {{ __('Add a unit first so the manager has something to manage.') }}
                            <flux:link :href="route('units.create')" wire:navigate>{{ __('New unit') }}</flux:link>
                        </flux:text>
                    @else
                        <flux:select wire:model="unitId" :label="__('Unit')" :placeholder="__('Choose a unit...')">
                            @foreach ($units as $unitOption)
                                <flux:select.option wire:key="invite-unit-{{ $unitOption->id }}" :value="$unitOption->id">{{ $unitOption->name }}</flux:select.option>
                            @endforeach
                        </flux:select>
                    @endif
                @endif
                <div class="flex items-center gap-4">
                    <flux:button type="submit" variant="primary" icon="envelope">{{ __('Send invitation') }}</flux:button>
                    <x-action-message on="invitation-sent">{{ __('Sent.') }}</x-action-message>
                </div>
            </form>
        </section>
    </div>
</div>
