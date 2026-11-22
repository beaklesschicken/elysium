<?php

namespace App\Livewire\Organizations;

use App\Enums\OrganizationRole;
use App\Livewire\Concerns\HasAddressFields;
use App\Mail\InvitationMail;
use App\Models\Invitation;
use App\Models\Organization;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
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

    /** Who will own it. Empty means the site admin creating it. */
    public string $ownerEmail = '';

    public function mount(): void
    {
        $this->authorize('create', Organization::class);
    }

    /**
     * @return array<string, array<int, string>>
     */
    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'ownerEmail' => ['nullable', 'string', 'email:rfc', 'max:150'],
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
        $this->authorize('create', Organization::class);

        $validated = $this->normalizeAddress($this->validate());
        $ownerEmail = Str::lower(trim($validated['ownerEmail'] ?? ''));
        unset($validated['ownerEmail']);
        $user = Auth::user();
        $ownsIt = $ownerEmail === '' || $ownerEmail === Str::lower($user->email);

        [$organization, $invitation, $token] = DB::transaction(function () use ($validated, $user, $ownsIt, $ownerEmail): array {
            $organization = Organization::create($validated);

            if ($ownsIt) {
                $organization->addMember($user, OrganizationRole::Owner);

                return [$organization, null, null];
            }

            [$invitation, $token] = Invitation::issue($organization, $ownerEmail, OrganizationRole::Owner, null, $user);

            return [$organization, $invitation, $token];
        });

        if ($invitation !== null) {
            Mail::to($invitation->email)->send(new InvitationMail($invitation, $organization->name, $user->name, null, $token));
            Flux::toast(variant: 'success', text: __(':organization created. Invitation sent to :email.', ['organization' => $organization->name, 'email' => $invitation->email]));
            $this->reset('name', 'ownerEmail', 'address', 'city', 'state_province', 'postcode');

            return;
        }

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
