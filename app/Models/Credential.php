<?php

namespace App\Models;

use App\Enums\CredentialStatus;
use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\CredentialFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\DB;

/**
 * One permit, certificate or training held by an employee, unit or the organization.
 * Renewing creates a new record and marks the old one superseded, so history is kept.
 */
class Credential extends Model
{
    /** @use HasFactory<CredentialFactory> */
    use BelongsToOrganization, HasFactory;

    protected $fillable = [
        'organization_id',
        'credential_type_id',
        'holder_type',
        'holder_id',
        'number',
        'issued_on',
        'expires_on',
        'notes',
        'verified_by',
        'verified_at',
        'superseded_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'issued_on' => 'date',
            'expires_on' => 'date',
            'verified_at' => 'datetime',
            'superseded_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<CredentialType, $this>
     */
    public function type(): BelongsTo
    {
        return $this->belongsTo(CredentialType::class, 'credential_type_id')->withoutGlobalScope('available');
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function holder(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return HasMany<CredentialDocument, $this>
     */
    public function documents(): HasMany
    {
        return $this->hasMany(CredentialDocument::class)->latest();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /**
     * @return HasMany<CredentialReminder, $this>
     */
    public function reminders(): HasMany
    {
        return $this->hasMany(CredentialReminder::class);
    }

    public function status(): CredentialStatus
    {
        if ($this->expires_on === null) {
            return CredentialStatus::Valid;
        }

        if ($this->expires_on->lt(today())) {
            return CredentialStatus::Expired;
        }

        if ($this->expires_on->lte(today()->addDays(CredentialStatus::EXPIRING_SOON_DAYS))) {
            return CredentialStatus::ExpiringSoon;
        }

        return CredentialStatus::Valid;
    }

    /**
     * Days until expiry; negative once expired, null when it never expires.
     */
    public function daysUntilExpiry(): ?int
    {
        return $this->expires_on === null ? null : (int) today()->diffInDays($this->expires_on, false);
    }

    public function isCurrent(): bool
    {
        return $this->superseded_at === null;
    }

    /**
     * Records the renewed credential and keeps this one as history.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function renew(array $attributes): self
    {
        return DB::transaction(function () use ($attributes): self {
            $renewed = static::create([
                'organization_id' => $this->organization_id,
                'credential_type_id' => $this->credential_type_id,
                'holder_type' => $this->holder_type,
                'holder_id' => $this->holder_id,
                ...$attributes,
            ]);

            $this->forceFill(['superseded_at' => now()])->save();

            return $renewed;
        });
    }

    /**
     * @param  Builder<Credential>  $query
     */
    public function scopeCurrent(Builder $query): void
    {
        $query->whereNull('superseded_at');
    }

    /**
     * @param  Builder<Credential>  $query
     */
    public function scopeExpired(Builder $query): void
    {
        $query->whereNotNull('expires_on')->whereDate('expires_on', '<', today());
    }

    /**
     * Not yet expired, but expiring within the given number of days.
     *
     * @param  Builder<Credential>  $query
     */
    public function scopeExpiringWithin(Builder $query, int $days): void
    {
        $query->whereNotNull('expires_on')
            ->whereDate('expires_on', '>=', today())
            ->whereDate('expires_on', '<=', today()->addDays($days));
    }

    /**
     * @param  Builder<Credential>  $query
     */
    public function scopeWithStatus(Builder $query, CredentialStatus $status): void
    {
        match ($status) {
            CredentialStatus::Expired => $query->expired(),
            CredentialStatus::ExpiringSoon => $query->expiringWithin(CredentialStatus::EXPIRING_SOON_DAYS),
            CredentialStatus::Valid => $query->where(fn (Builder $inner) => $inner
                ->whereNull('expires_on')
                ->orWhereDate('expires_on', '>', today()->addDays(CredentialStatus::EXPIRING_SOON_DAYS))),
        };
    }

    /**
     * Credentials the user may see: admins see all; managers see their units, those units'
     * employees, and the organization's own permits.
     *
     * @param  Builder<Credential>  $query
     */
    public function scopeVisibleTo(Builder $query, User $user): void
    {
        if ($user->isOrganizationAdmin()) {
            return;
        }

        $unitIds = $user->managedUnits()->pluck('units.id');
        $employeeIds = Employee::query()->visibleTo($user)->pluck('id');

        $query->where(fn (Builder $inner) => $inner
            ->where('holder_type', 'organization')
            ->orWhere(fn (Builder $units) => $units->where('holder_type', 'unit')->whereIn('holder_id', $unitIds))
            ->orWhere(fn (Builder $employees) => $employees->where('holder_type', 'employee')->whereIn('holder_id', $employeeIds)));
    }
}
