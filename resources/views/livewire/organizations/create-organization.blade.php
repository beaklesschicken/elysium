<div class="flex flex-col gap-6">
    <x-auth-header :title="__('Set up your organization')" :description="__('The business that owns your restaurants, bars or venues. You can add each location next.')" />

    <form wire:submit="save" class="flex flex-col gap-6">
        <flux:input wire:model="name" :label="__('Organization name')" required autofocus />

        <x-address-fields />

        <flux:button type="submit" variant="primary" class="w-full">
            {{ __('Create organization') }}
        </flux:button>
    </form>

    @if ($hasOrganizations)
        <div class="text-center text-sm">
            <flux:link :href="route('dashboard')" wire:navigate>{{ __('Back to dashboard') }}</flux:link>
        </div>
    @else
        <div class="text-center text-sm text-zinc-600 dark:text-zinc-400">
            {{ __('Were you invited by someone? Use the link in your invitation email instead.') }}
        </div>
    @endif
</div>
