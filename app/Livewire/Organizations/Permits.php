<?php

namespace App\Livewire\Organizations;

use App\Models\Credential;
use App\Models\Organization;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Organization permits')]
class Permits extends Component
{
    public function mount(): void
    {
        Gate::authorize('viewHolder', [Credential::class, $this->organization()]);
    }

    private function organization(): Organization
    {
        return Auth::user()->currentOrganization;
    }

    public function render(): View
    {
        return view('livewire.organizations.permits', ['organization' => $this->organization()]);
    }
}
