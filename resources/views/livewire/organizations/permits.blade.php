<div class="flex w-full flex-col gap-6">
    <div>
        <flux:heading size="xl" level="1">{{ __('Organization permits') }}</flux:heading>
        <flux:subheading>{{ __('Licenses, registrations and policies held by :name itself. Location permits live on each unit.', ['name' => $organization->name]) }}</flux:subheading>
    </div>

    <livewire:credentials.holder-credentials :holder="$organization" :heading="__('Licenses & registrations')" :key="'organization-credentials-'.$organization->id" />
</div>
