<?php

namespace App\Models;

use App\Jobs\SyncSubscriptionQuantity;
use App\Models\Concerns\BelongsToOrganization;
use App\Models\Concerns\HasCredentials;
use Database\Factories\UnitFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * A location or outlet of an organization (restaurant, bar, venue).
 */
class Unit extends Model
{
    /** @use HasFactory<UnitFactory> */
    use BelongsToOrganization, HasCredentials, HasFactory;

    protected $fillable = [
        'organization_id',
        'name',
        'description',
        'address',
        'city',
        'state_province',
        'postcode',
        'phone',
    ];

    /**
     * The subscription is billed per unit, so adding or removing one changes the quantity.
     */
    protected static function booted(): void
    {
        static::created(fn (Unit $unit) => SyncSubscriptionQuantity::dispatch((int) $unit->organization_id)->afterCommit());
        static::deleted(fn (Unit $unit) => SyncSubscriptionQuantity::dispatch((int) $unit->organization_id)->afterCommit());
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function managers(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withTimestamps();
    }

    /**
     * @return BelongsToMany<Employee, $this>
     */
    public function employees(): BelongsToMany
    {
        return $this->belongsToMany(Employee::class)->withTimestamps();
    }

    /**
     * Admins see every unit; managers only the units they manage.
     *
     * @param  Builder<Unit>  $query
     */
    public function scopeVisibleTo(Builder $query, User $user): void
    {
        if ($user->isOrganizationAdmin()) {
            return;
        }

        $query->whereHas('managers', fn (Builder $managers) => $managers->whereKey($user->id));
    }
}
