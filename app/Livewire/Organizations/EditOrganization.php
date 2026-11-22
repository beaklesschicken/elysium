<?php

namespace App\Livewire\Organizations;

use App\Livewire\Concerns\HasAddressFields;
use App\Models\Organization;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Organization details')]
class EditOrganization extends Component
{
    use HasAddressFields;

    public string $name = '';

    public bool $credentialRemindersEnabled = false;

    public function mount(): void
    {
        $organization = $this->organization();
        $this->authorize('manage', $organization);

        $this->name = $organization->name;
        $this->credentialRemindersEnabled = $organization->credential_reminders_enabled;
        $this->fillAddressFrom($organization);
    }

    /**
     * @return array<string, array<int, string>>
     */
    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'credentialRemindersEnabled' => ['boolean'],
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

        $validated = $this->normalizeAddress($this->validate());
        $validated['credential_reminders_enabled'] = $validated['credentialRemindersEnabled'];
        unset($validated['credentialRemindersEnabled']);

        $organization->update($validated);

        Flux::toast(variant: 'success', text: __('Organization details saved.'));
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
