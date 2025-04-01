<x-layouts.app>
    <x-card-single>
        <flux:heading size="xl" class="pb-4 ps-4">{{ Session::get('working_org')->name }} Teams</flux:heading>
        <div class="flex flex-row gap-4">
            <div class=" w-1/3 rounded-xl border border-neutral-200 dark:border-neutral-700 p-4 flex flex-col gap-2">
                @foreach($teams as $team)
                    <a class='p-2 flex w-full {{ request()->path() === "teams/$team->id" ? "bg-zinc-50 dark:bg-zinc-700" : "" }} hover:bg-zinc-50 dark:hover:bg-zinc-700 hover:cursor-pointer rounded-xl'
                        href="/teams/{{ $team->id }}">
                        {{ $team->name }}
                    </a>
                @endforeach
            </div>
            <div class="border w-2/3 rounded-xl border-neutral-200 dark:border-neutral-700 p-4">
                @if($team_id <> 0)
                    @foreach($working_team as $employee)
                        <a
                            class="p-2 flex flex-col w-full hover:bg-zinc-50 dark:hover:bg-zinc-700 hover:cursor-pointer rounded-xl">
                            <div>{{ $employee->name }}</div>
                            <div class="flex justify-between text-xs">
                                <div><span class="font-semibold">Birthday:</span> {{ \Carbon\Carbon::parse($employee->birth_date)->format('m/d') }}</div>
                                <div><span class="font-semibold">Start:</span> {{ \Carbon\Carbon::parse($employee->start_date)->format('m/d/y') }}</div>
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