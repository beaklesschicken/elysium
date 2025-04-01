<div>
    <form>
        <div class="p-2">
            <flux:input wire:model='name' label='Team Name'/>
        </div>
        <div class="p-2">
            <flux:input wire:model='description' label='Description or Address' />
        </div>
        <div class="p-2 flex justify-between">
            <flux:button type="submit" icon="check">Save</flux:button>
            <flux:button variant='danger' icon="x-mark">Cancel</flux:button>
        </div>
    </form>
</div>
