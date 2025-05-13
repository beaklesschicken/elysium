<x-layouts.app>
    <x-card-single>
        <div class="flex flex-row justify-between gap-4">
            <flux:heading size="xl" class="pb-4 ps-4">{{ Session::get('working_org')->name }} Teams</flux:heading>
            <flux:button href="/teams/new">New Team</flux:button>
        </div>
        <div class="flex flex-row gap-4">
            {{-- Teams Div --}}
            <div class=" w-1/3 rounded-lg border border-neutral-200 dark:border-neutral-700 p-4 flex flex-col gap-2 hidden sm:block">
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
            {{-- Names Div --}}
            <div class="border w-2/3 rounded-lg border-neutral-200 dark:border-neutral-700 p-4">

                @if($team_id <> 0)
                     {{-- [ ] add employees and managers to this team from here. --}}
                    @livewire('teams.add-to-team')
                    <div class="grid grid-cols-8 gap-2 border-b p-4 mb-2">
                        <div class="font-semibold col-span-5">Employee</div>
                        <div class="font-semibold justify-self-center" tooltip.top="Permits expiring within 30 days">30 Days</div>
                        <div class="font-semibold justify-self-center">90 Days</div>
                        <div class=""></div>                        
                    </div>
                    @foreach($working_team_managers as $manager)
                        <a
                            class="py-0 px-3 h-10 items-center justify-between w-full hover:bg-zinc-50 dark:hover:bg-zinc-700 hover:cursor-pointer rounded-xl  grid grid-cols-8 gap-2">
                            <div class="col-span-4">{{ $manager->name }}</div>
                            <div>Manager</div>
                            <div class="justify-self-center">{{rand(1,4)}}</div>
                            <div class="justify-self-center">{{ rand(1,6)}}</div>
                            {{-- [ ] remove members from this team --}}
                            <flux:icon icon="circle-x" class="align-middle justify-self-end"/>
                        </a>
                    @endforeach
                    @foreach($working_team as $employee)
                        <a
                            class="py-0 px-3 h-10 items-center justify-between w-full hover:bg-zinc-50 dark:hover:bg-zinc-700 hover:cursor-pointer rounded-xl  grid grid-cols-8 gap-2">
                            <div class="col-span-5">{{ $employee->name }}</div>

                            <div class="justify-self-center">{{rand(1,4)}}</div>
                            <div class="justify-self-center">{{ rand(1,6)}}</div>
                            <flux:icon icon="circle-x" class="align-middle justify-self-end"/>
                        </a>
                    @endforeach
                @else
                    Please select a team
                @endif
            </div>
        </div>
    </x-card-single>
</x-layouts.app>