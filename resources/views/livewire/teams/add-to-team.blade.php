<div>
    <form 
        x-data='{
            userSelected(e) {
            let value = e.target.value
            let id = document.body.querySelector("datalist [value=\""+value+"\"]").dataset.value
            console.log(id);
            }
        }'
        wire:submit='changeIncoming()'
    >
    <div class="flex gap-4">

        <flux:input 
            type="text" 
            list="options" 
            name="incoming" 
            id="incoming" 
            wire:model.live.debounce='incoming'  
            x-on:change.debounce="userSelected($event)" 
            placeholder="Add employee or manager"/>
            <flux:button>Add</flux:button>

        </div>
        <datalist id="options" name="options" class="mt-2">
            {{-- // TODO make managers appear first, then employees --}}
            @foreach($managers as $manager)
            <option
                wire:key='{{ $manager['user_id'] }}'
                data-value='{{ $manager['user_id'] }}'
                value="{{ $manager['name']}}"
            >
            </option>
            @endforeach
            @foreach($employees as $employee)
            <option
                wire:key='{{ $employee['emp_id'] }}'
                data-value='{{ $employee['emp_id'] }}'
                value="{{ $employee['name']}}"
            >
            </option>
            @endforeach
    </datalist>
    <div class="text-white border-1 rounded-md px-4">{{json_encode($managers) }}</div>
    </form>
</div>
