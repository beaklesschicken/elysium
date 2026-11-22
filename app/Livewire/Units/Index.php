<?php

namespace App\Livewire\Units;

use App\Models\Unit;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Units')]
class Index extends Component
{
    public function mount(): void
    {
        $this->authorize('viewAny', Unit::class);
    }

    public function render(): View
    {
        $units = Unit::query()
            ->visibleTo(Auth::user())
            ->with('managers:users.id,users.name')
            ->withCount('employees')
            ->orderBy('name')
            ->get();

        return view('livewire.units.index', ['units' => $units]);
    }
}
