<?php

namespace App\Models;

use App\Enums\OrganizationRole;
use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\InvitationFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class Invitation extends Model
{
    /** @use HasFactory<InvitationFactory> */
    use BelongsToOrganization, HasFactory;

    public const DAYS_VALID = 7;

    protected $fillable = [
        'organization_id',
        'unit_id',
        'invited_by',
        'email',
        'role',
        'token_hash',
        'expires_at',
        'accepted_at',
    ];

    protected $hidden = ['token_hash'];

    /**
     * @return array<string, string|class-string>
     */
    protected function casts(): array
    {
        return [
            'role' => OrganizationRole::class,
            'expires_at' => 'datetime',
            'accepted_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Unit, $this>
     */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class)->withoutGlobalScope('organization');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    /**
     * @param  Builder<Invitation>  $query
     */
    public function scopePending(Builder $query): void
    {
        $query->whereNull('accepted_at')->where('expires_at', '>', now());
    }

    /**
     * Creates an invitation and returns it with the plain token, which is only ever sent by email.
     *
     * @return array{0: Invitation, 1: string}
     */
    public static function issue(Organization $organization, string $email, OrganizationRole $role, ?Unit $unit, User $inviter): array
    {
        $token = Str::random(48);

        $invitation = static::create([
            'organization_id' => $organization->id,
            'unit_id' => $unit?->id,
            'invited_by' => $inviter->id,
            'email' => Str::lower($email),
            'role' => $role,
            'token_hash' => static::hashToken($token),
            'expires_at' => now()->addDays(self::DAYS_VALID),
        ]);

        return [$invitation, $token];
    }

    public static function findPendingByToken(string $token): ?self
    {
        return static::withoutGlobalScope('organization')
            ->pending()
            ->where('token_hash', static::hashToken($token))
            ->first();
    }

    public static function hashToken(string $token): string
    {
        return hash('sha256', $token);
    }

    public function isFor(User $user): bool
    {
        return Str::lower($user->email) === $this->email;
    }

    /**
     * Adds the user to the organization (and unit), keeping any higher role they already hold.
     */
    public function accept(User $user): void
    {
        DB::transaction(function () use ($user): void {
            $organization = Organization::findOrFail($this->organization_id);
            $existingRole = $user->roleIn($organization);

            if ($existingRole === null || ($existingRole === OrganizationRole::Manager && $this->role !== OrganizationRole::Manager)) {
                $organization->addMember($user, $this->role);
            }

            if ($this->unit_id !== null) {
                $user->managedUnits()->syncWithoutDetaching([$this->unit_id]);
            }

            $this->forceFill(['accepted_at' => now()])->save();
            $user->forceFill(['current_organization_id' => $organization->id])->save();
            $user->unsetRelation('organizations');
        });
    }
}
