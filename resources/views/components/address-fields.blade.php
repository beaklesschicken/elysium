<div class="flex flex-col gap-6">
    <flux:input wire:model="address" :label="__('Street address')" autocomplete="street-address" />
    <div class="grid gap-6 sm:grid-cols-6">
        <flux:input wire:model="city" :label="__('City')" class="sm:col-span-3" autocomplete="address-level2" />
        <flux:input wire:model="state_province" :label="__('State')" maxlength="2" class="sm:col-span-1" autocomplete="address-level1" placeholder="IN" />
        <flux:input wire:model="postcode" :label="__('Zipcode')" class="sm:col-span-2" autocomplete="postal-code" />
    </div>
</div>
