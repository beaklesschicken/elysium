<?php

namespace App\Livewire\Organizations;

use App\Enums\EmployeeStatus;
use App\Enums\OrganizationRole;
use App\Mail\InvitationMail;
use App\Models\Employee;
use App\Models\Invitation;
use App\Models\Organization;
use App\Models\Unit;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('People & access')]
class Members extends Component
{
    public const MAX_INVITES_PER_HOUR = 30;

    public string $email = '';

    public string $role = 'manager';

    public string $unitId = '';

    public function mount(): void
    {
        $this->authorize('manage', $this->organization());
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    protected function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email:rfc', 'max:150'],
            'role' => ['required', Rule::in(array_map(fn (OrganizationRole $role) => $role->value, OrganizationRole::invitable()))],
            'unitId' => [
                Rule::requiredIf($this->role === OrganizationRole::Manager->value),
                'nullable',
                Rule::in(Unit::query()->pluck('id')->all()),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            'unitId.required' => __('Choose the unit this person will manage.'),
        ];
    }

    public function invite(): void
    {
        $organization = $this->organization();
        $this->authorize('manage', $organization);
        $validated = $this->validate();
        $email = Str::lower(trim($validated['email']));

        if ($organization->users()->where('email', $email)->exists()) {
            throw ValidationException::withMessages(['email' => __('This person is already in your organization.')]);
        }

        $rateLimitKey = 'invitations:'.$organization->id;
        if (RateLimiter::tooManyAttempts($rateLimitKey, self::MAX_INVITES_PER_HOUR)) {
            throw ValidationException::withMessages(['email' => __('Too many invitations sent. Try again later.')]);
        }
        RateLimiter::hit($rateLimitKey, 3600);

        $role = OrganizationRole::from($validated['role']);
        $unit = $role === OrganizationRole::Manager ? Unit::find((int) $validated['unitId']) : null;
        $inviter = Auth::user();

        [$invitation, $token] = DB::transaction(function () use ($organization, $email, $role, $unit, $inviter): array {
            Invitation::query()->pending()->where('email', $email)->delete();

            return Invitation::issue($organization, $email, $role, $unit, $inviter);
        });

        Mail::to($email)->send(new InvitationMail($invitation, $organization->name, $inviter->name, $unit?->name, $token));

        $this->reset('email', 'unitId');

        Flux::toast(variant: 'success', text: __('Invitation sent to :email.', ['email' => $email]));
    }

    public function revokeInvitation(int $invitationId): void
    {
        $this->authorize('manage', $this->organization());

        if (Invitation::query()->whereKey($invitationId)->whereNull('accepted_at')->delete() > 0) {
            Flux::toast(text: __('Invitation revoked.'));
        }
    }

    public function changeRole(int $userId, string $newRole): void
    {
        $organization = $this->organization();
        $this->authorize('manage', $organization);

        $role = OrganizationRole::tryFrom($newRole);
        $member = $organization->users()->whereKey($userId)->first();

        if ($role === null || $member === null) {
            return;
        }

        $currentRole = OrganizationRole::from($member->pivot->role);
        $actingRole = Auth::user()->roleIn($organization);

        if (($currentRole === OrganizationRole::Owner || $role === OrganizationRole::Owner) && $actingRole !== OrganizationRole::Owner) {
            $this->addError('members', __('Only an owner can change who owns the organization.'));

            return;
        }

        if ($currentRole === OrganizationRole::Owner && $role !== OrganizationRole::Owner && $this->ownerCount($organization) <= 1) {
            $this->addError('members', __('The organization needs at least one owner.'));

            return;
        }

        $organization->users()->updateExistingPivot($userId, ['role' => $role->value]);

        Flux::toast(variant: 'success', text: __(':name is now :role.', ['name' => $member->name, 'role' => $role->label()]));
    }

    public function removeMember(int $userId): void
    {
        $organization = $this->organization();
        $this->authorize('manage', $organization);

        $member = $organization->users()->whereKey($userId)->first();

        if ($member === null) {
            return;
        }

        if ($member->id === Auth::id()) {
            $this->addError('members', __('You can\'t remove yourself.'));

            return;
        }

        if (OrganizationRole::from($member->pivot->role) === OrganizationRole::Owner && Auth::user()->roleIn($organization) !== OrganizationRole::Owner) {
            $this->addError('members', __('Only an owner can remove an owner.'));

            return;
        }

        DB::transaction(function () use ($organization, $member): void {
            $member->managedUnits()->detach(Unit::query()->pluck('id'));
            $organization->users()->detach($member->id);

            if ((int) $member->current_organization_id === $organization->id) {
                $member->forceFill(['current_organization_id' => null])->save();
            }
        });

        Flux::toast(text: __(':name was removed.', ['name' => $member->name]));
    }

    /**
     * Puts a member on the employee list so their own credentials are tracked. Links an existing
     * employee with the same email if there is one; managers are added to the units they manage.
     */
    public function trackAsEmployee(int $userId): void
    {
        $organization = $this->organization();
        $this->authorize('manage', $organization);

        $member = $organization->users()->whereKey($userId)->first();

        if ($member === null) {
            return;
        }

        $employee = DB::transaction(function () use ($member): Employee {
            $existing = Employee::query()->where('user_id', $member->id)->first()
                ?? Employee::query()->whereNull('user_id')->where('email', $member->email)->first();

            if ($existing !== null) {
                $existing->forceFill(['user_id' => $member->id])->save();

                return $existing;
            }

            $employee = new Employee([
                'name' => $member->name,
                'email' => $member->email,
                'status' => EmployeeStatus::Active,
            ]);
            $employee->forceFill(['user_id' => $member->id])->save();
            $employee->units()->attach($member->managedUnits()->whereIn('units.id', Unit::query()->select('id'))->pluck('units.id'));

            return $employee;
        });

        Flux::toast(variant: 'success', text: __(':name is on the employee list.', ['name' => $member->name]));

        $this->redirect(route('employees.show', $employee, absolute: false), navigate: true);
    }

    private function ownerCount(Organization $organization): int
    {
        return $organization->users()->wherePivot('role', OrganizationRole::Owner->value)->count();
    }

    private function organization(): Organization
    {
        return Auth::user()->currentOrganization;
    }

    public function render(): View
    {
        $organization = $this->organization();

        return view('livewire.organizations.members', [
            'members' => $organization->users()->with('managedUnits:units.id,units.name')->orderBy('name')->get(),
            'employeeIdsByUser' => Employee::query()->whereNotNull('user_id')->pluck('id', 'user_id'),
            'invitations' => Invitation::query()->pending()->with('unit:id,name')->latest()->get(),
            'units' => Unit::query()->orderBy('name')->get(['id', 'name']),
            'roles' => OrganizationRole::cases(),
            'invitableRoles' => OrganizationRole::invitable(),
            'actingRole' => Auth::user()->roleIn($organization),
        ]);
    }
}
