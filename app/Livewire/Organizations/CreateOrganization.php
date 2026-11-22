<?php

namespace App\Livewire\Organizations;

use App\Enums\OrganizationRole;
use App\Livewire\Concerns\HasAddressFields;
use App\Models\Organization;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.auth')]
#[Title('Set up your organization')]
class CreateOrganization extends Component
{
    use HasAddressFields;

    public string $name = '';

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
        $validated = $this->normalizeAddress($this->validate());
        $user = Auth::user();

        $organization = DB::transaction(function () use ($validated, $user): Organization {
            $organization = Organization::create($validated);
            $organization->addMember($user, OrganizationRole::Owner);

            return $organization;
        });

        $user->unsetRelation('organizations');
        $user->switchOrganization($organization);

        $this->redirect(route('dashboard', absolute: false), navigate: true);
    }

    public function render(): View
    {
        return view('livewire.organizations.create-organization', [
            'hasOrganizations' => Auth::user()->organizations()->exists(),
        ]);
    }
}
