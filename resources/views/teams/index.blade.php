<x-layouts.app>
    <x-card-single>
        <div class="flex flex-row justify-between gap-4">
            <flux:heading size="xl" class="pb-4 ps-4">{{ Session::get('working_org')->name }} Teams</flux:heading>
            <flux:button href="/teams/new">New Team</flux:button>
        </div>
        <div class="flex flex-row gap-4">
            <div class=" w-1/3 rounded-lg border border-neutral-200 dark:border-neutral-700 p-4 flex flex-col gap-2">
                @foreach($teams as $team)
                    <a class='py-0 px-3 h-10 items-center flex w-full 
                        {{ request()->path() === "teams/$team->id" ? "bg-zinc-50 dark:bg-zinc-800" : "" }} 
                         hover:bg-zinc-50 dark:hover:bg-zinc-700 hover:cursor-pointer rounded-xl'
                        href="/teams/{{ $team->id }}"
                        wire:navigate >
                        {{ $team->name }}
                    </a>
                @endforeach
            </div>
            <div class="border w-2/3 rounded-lg border-neutral-200 dark:border-neutral-700 p-4 flex flex-col  gap-2">
                @if($team_id <> 0)
                     {{-- [ ] add employees and managers to this team from here. --}}
                    @livewire('teams.add-to-team')
                    @foreach($working_team_managers as $manager)
                        <a
                            class="py-0 px-3 h-10 items-center justify-between flex flex-row gap-2 w-full hover:bg-zinc-50 dark:hover:bg-zinc-700 hover:cursor-pointer rounded-xl">
                            <div>{{ $manager->name }}</div>
                            <div class="flex justify-between text-xs">
                                <div><span class="font-semibold">30 Days:</span> {{rand(1,4)}}</div>
                                <div><span class="font-semibold">90 Days:</span> {{ rand(1,6)}}</div>
                            </div>
                            {{-- [ ] remove members from this team --}}
                            <flux:icon icon="circle-x"/>
                        </a>
                    @endforeach
                    @foreach($working_team as $employee)
                        <a
                            class="py-0 px-3 h-10 items-center justify-between flex flex-row gap-2 w-full hover:bg-zinc-50 dark:hover:bg-zinc-700 hover:cursor-pointer rounded-xl">
                            <div>{{ $employee->name }}</div>
                            <div class="flex justify-between text-xs">
                                <div><span class="font-semibold">30 Days:</span> {{rand(1,4)}}</div>
                                <div><span class="font-semibold">90 Days:</span> {{ rand(1,6)}}</div>
                            </div>
                        </a>
                    @endforeach
                @else
                    Please select a team
                @endif
            </div>
        </div>
    </x-card-single>
</x-layouts.app>