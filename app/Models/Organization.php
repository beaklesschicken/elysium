<?php

namespace App\Models;

use App\Enums\OrganizationRole;
use App\Models\Concerns\HasCredentials;
use Database\Factories\OrganizationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Organization extends Model
{
    /** @use HasFactory<OrganizationFactory> */
    use HasCredentials, HasFactory;

    protected $fillable = [
        'name',
        'address',
        'city',
        'state_province',
        'postcode',
        'credential_reminders_enabled',
    ];

    /**
     * Days before expiry when reminder emails go out; 0 means on or after the expiry date.
     *
     * @var list<int>
     */
    public const REMINDER_DAYS = [90, 30, 7, 0];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'credential_reminders_enabled' => 'boolean',
        ];
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withPivot('role')->withTimestamps();
    }

    /**
     * @return HasMany<Unit, $this>
     */
    public function units(): HasMany
    {
        return $this->hasMany(Unit::class);
    }

    /**
     * @return HasMany<Employee, $this>
     */
    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    /**
     * @return HasMany<Invitation, $this>
     */
    public function invitations(): HasMany
    {
        return $this->hasMany(Invitation::class);
    }

    /**
     * @return HasMany<CredentialType, $this>
     */
    public function credentialTypes(): HasMany
    {
        return $this->hasMany(CredentialType::class);
    }

    public function addMember(User $user, OrganizationRole $role): void
    {
        $this->users()->syncWithoutDetaching([$user->id => ['role' => $role->value]]);
    }
}
