<?php

namespace App\Models;

use App\Enums\OrganizationRole;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_site_admin' => 'boolean',
        ];
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->map(fn (string $name) => Str::of($name)->substr(0, 1))
            ->implode('');
    }

    /**
     * @return BelongsToMany<Organization, $this>
     */
    public function organizations(): BelongsToMany
    {
        return $this->belongsToMany(Organization::class)->withPivot('role')->withTimestamps();
    }

    /**
     * @return BelongsTo<Organization, $this>
     */
    public function currentOrganization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'current_organization_id');
    }

    /**
     * Units this user manages, across all of their organizations.
     *
     * @return BelongsToMany<Unit, $this>
     */
    public function managedUnits(): BelongsToMany
    {
        return $this->belongsToMany(Unit::class)->withTimestamps();
    }

    public function roleIn(Organization|int|null $organization): ?OrganizationRole
    {
        $organizationId = $organization instanceof Organization ? $organization->id : $organization;

        if ($organizationId === null) {
            return null;
        }

        $role = $this->organizations->firstWhere('id', $organizationId)?->pivot->role;

        return $role === null ? null : OrganizationRole::from($role);
    }

    public function belongsToOrganization(Organization|int $organization): bool
    {
        return $this->roleIn($organization) !== null;
    }

    public function currentRole(): ?OrganizationRole
    {
        return $this->roleIn($this->current_organization_id);
    }

    /**
     * Owner or admin of the current organization.
     */
    public function isOrganizationAdmin(): bool
    {
        return $this->currentRole()?->canManageOrganization() ?? false;
    }

    public function managesUnit(Unit $unit): bool
    {
        return $this->managedUnits()->whereKey($unit->id)->exists();
    }

    /**
     * Runs the service itself; can create organizations while sign-up is invite-only.
     */
    public function isSiteAdmin(): bool
    {
        return (bool) $this->is_site_admin;
    }

    public function switchOrganization(Organization $organization): bool
    {
        if (! $this->belongsToOrganization($organization)) {
            return false;
        }

        $this->forceFill(['current_organization_id' => $organization->id])->save();
        $this->setRelation('currentOrganization', $organization);

        return true;
    }
}
