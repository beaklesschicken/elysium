<?php

namespace App\Livewire\Organizations;

use App\Livewire\Concerns\HasAddressFields;
use App\Models\Organization;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Organization details')]
class EditOrganization extends Component
{
    use HasAddressFields;

    public string $name = '';

    public function mount(): void
    {
        $organization = $this->organization();
        $this->authorize('manage', $organization);

        $this->name = $organization->name;
        $this->fillAddressFrom($organization);
    }

    /**
     * @return array<string, array<int, string>>
     */
    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            ...$this->addressRules(),
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return $this->addressMessages();
    }

    public function save(): void
    {
        $organization = $this->organization();
        $this->authorize('manage', $organization);

        $organization->update($this->normalizeAddress($this->validate()));

        $this->dispatch('organization-saved');
    }

    private function organization(): Organization
    {
        return Auth::user()->currentOrganization;
    }

    public function render(): View
    {
        return view('livewire.organizations.edit-organization');
    }
}
