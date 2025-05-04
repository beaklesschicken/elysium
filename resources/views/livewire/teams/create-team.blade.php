<div>
    <form wire:submit='save'>
        <div class="p-2">
            <flux:input wire:model='name' name="name" label='Team Name' placeholder="New Team Name"/>
        </div>
        <div class="p-2">
            <flux:input wire:model='description' name="description" label='Description or Address' />
        </div>
        <div class="p-2">
            <flux:select wire:model='manager' name="manager" label='Manager' class="mb-4">
                @foreach($users as $user)
                    <option value="{{ $user->id }}" selected>{{ $user->name }}</option>
                @endforeach

            </flux:select>
            <flux:callout icon="info" variant="secondary" inline>
                <flux:callout.heading>Managers will be users that have login credentials. If there are no other registered users, you will be selected by default.</flux:callout.heading>
            </flux:callout>
        </div>
        <div class="p-2 flex justify-between">
            <flux:button type="submit" icon="check">Save</flux:button>
            <flux:button variant='danger' type="link" href="/teams/" icon="x-mark" >Cancel</flux:button>
        </div>
    </form>
</div>
