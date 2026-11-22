<div class="flex flex-col gap-6">
    <flux:input wire:model="address" :label="__('Street address')" autocomplete="street-address" />
    <div class="grid gap-6 sm:grid-cols-6">
        <div class="sm:col-span-3">
            <flux:input wire:model="city" :label="__('City')" autocomplete="address-level2" />
        </div>
        <div class="sm:col-span-1">
            <flux:input wire:model="state_province" :label="__('State')" maxlength="2" autocomplete="address-level1" placeholder="IN" />
        </div>
        <div class="sm:col-span-2">
            <flux:input wire:model="postcode" :label="__('Zipcode')" autocomplete="postal-code" />
        </div>
    </div>
</div>
