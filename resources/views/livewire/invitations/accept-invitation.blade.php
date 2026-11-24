<div class="flex flex-col gap-6">
    @if ($invitation === null)
        <x-auth-header :title="__('Invitation unavailable')" :description="__('This invitation has expired, was revoked, or was already used. Ask for a new one.')" />
    @else
        <x-auth-header
            :title="__('Join :organization', ['organization' => $organizationName])"
            :description="__('You were invited as :role:unit.', ['role' => $invitation->role->label(), 'unit' => $invitation->unit ? ' '.__('for :unit', ['unit' => $invitation->unit->name]) : ''])" />

        @if ($signedInUser)
            @if ($emailMatches)
                <flux:button wire:click="accept" variant="primary" class="w-full">{{ __('Accept invitation') }}</flux:button>
            @else
                <flux:callout icon="exclamation-triangle" color="amber">
                    <flux:callout.text>
                        {{ __('This invitation is for :invited, but you are signed in as :current.', ['invited' => $invitation->email, 'current' => $signedInUser->email]) }}
                    </flux:callout.text>
                </flux:callout>
                <flux:button wire:click="switchAccount" variant="primary" class="w-full">{{ __('Log out and continue as :invited', ['invited' => $invitation->email]) }}</flux:button>
            @endif
        @elseif ($hasAccount)
            <flux:text class="text-center">{{ __('You already have an account for :email. Log in to accept.', ['email' => $invitation->email]) }}</flux:text>
            <flux:button :href="route('login')" variant="primary" class="w-full">{{ __('Log in') }}</flux:button>
        @else
            <form wire:submit="register" class="flex flex-col gap-6">
                <flux:input :label="__('Email address')" :value="$invitation->email" disabled />
                <flux:input wire:model="name" :label="__('Name')" required autofocus autocomplete="name" />
                <flux:input wire:model="password" :label="__('Password')" type="password" required autocomplete="new-password" />
                <flux:input wire:model="password_confirmation" :label="__('Confirm password')" type="password" required autocomplete="new-password" />
                <flux:button type="submit" variant="primary" class="w-full">{{ __('Create account and join') }}</flux:button>
            </form>
        @endif
    @endif
</div>
