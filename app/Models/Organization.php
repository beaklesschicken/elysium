<?php

namespace App\Models;

use App\Enums\BillingInterval;
use App\Enums\EmployeeStatus;
use App\Enums\OrganizationRole;
use App\Enums\Plan;
use App\Models\Concerns\HasCredentials;
use Database\Factories\OrganizationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Cashier\Billable;
use Laravel\Cashier\Subscription;

class Organization extends Model
{
    /** @use HasFactory<OrganizationFactory> */
    use Billable, HasCredentials, HasFactory;

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
            'trial_ends_at' => 'datetime',
        ];
    }

    /**
     * Every new organization starts on a generic (no card) trial.
     */
    protected static function booted(): void
    {
        static::creating(function (Organization $organization): void {
            if (! $organization->isDirty('trial_ends_at')) {
                $organization->trial_ends_at = now()->addDays((int) config('billing.trial_days'));
            }
        });
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

    /**
     * @return BelongsToMany<User, $this>
     */
    public function owners(): BelongsToMany
    {
        return $this->users()->wherePivot('role', OrganizationRole::Owner->value);
    }

    public function activeSubscription(): ?Subscription
    {
        $subscription = $this->subscription(config('billing.subscription_type'));

        return $subscription?->valid() ? $subscription : null;
    }

    public function plan(): Plan
    {
        if ($this->activeSubscription() !== null) {
            return Plan::Standard;
        }

        return $this->onGenericTrial() ? Plan::Trial : Plan::Free;
    }

    public function billingInterval(): ?BillingInterval
    {
        return BillingInterval::fromStripePrice($this->activeSubscription()?->stripe_price);
    }

    /**
     * Units billed on the subscription: one per unit, at least one.
     */
    public function billableUnitCount(): int
    {
        return max(1, $this->units()->withoutGlobalScope('organization')->count());
    }

    public function activeEmployeeCount(): int
    {
        return $this->employees()->withoutGlobalScope('organization')->where('status', EmployeeStatus::Active)->count();
    }

    public function canAddUnit(): bool
    {
        $limit = $this->plan()->unitLimit();

        return $limit === null || $this->units()->withoutGlobalScope('organization')->count() < $limit;
    }

    public function canAddEmployees(int $count = 1): bool
    {
        $limit = $this->plan()->employeeLimit();

        return $limit === null || $this->activeEmployeeCount() + $count <= $limit;
    }

    /**
     * Stripe customers are named after the organization and billed to its first owner.
     */
    public function stripeName(): ?string
    {
        return $this->name;
    }

    public function stripeEmail(): ?string
    {
        return $this->owners()->orderBy('organization_user.created_at')->value('email');
    }

    /**
     * @return array{line1: ?string, city: ?string, state: ?string, postal_code: ?string, country: string}|null
     */
    public function stripeAddress(): ?array
    {
        if (blank($this->address) && blank($this->city)) {
            return null;
        }

        return [
            'line1' => $this->address,
            'city' => $this->city,
            'state' => $this->state_province,
            'postal_code' => $this->postcode,
            'country' => 'US',
        ];
    }
}
