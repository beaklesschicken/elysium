<?php

namespace App\Models;

use App\Enums\EmployeeStatus;
use App\Enums\OrganizationRole;
use App\Models\Concerns\BelongsToOrganization;
use App\Models\Concerns\HasCredentials;
use Database\Factories\EmployeeFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * A person whose credentials (and later tips) are tracked. Most employees never log in;
 * owners, admins and managers who need their own credentials tracked are linked through user_id.
 */
class Employee extends Model
{
    /** @use HasFactory<EmployeeFactory> */
    use BelongsToOrganization, HasCredentials, HasFactory;

    protected $fillable = [
        'organization_id',
        'name',
        'email',
        'phone',
        'start_date',
        'birth_date',
        'status',
        'notes',
    ];

    /**
     * @return array<string, string|class-string>
     */
    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'birth_date' => 'date',
            'status' => EmployeeStatus::class,
        ];
    }

    /**
     * The portal account of this same person, if they log in.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The linked account's role in this employee's organization; null for employees who don't log in.
     */
    public function portalRole(): ?OrganizationRole
    {
        return $this->user?->roleIn($this->organization_id);
    }

    /**
     * @return BelongsToMany<Unit, $this>
     */
    public function units(): BelongsToMany
    {
        return $this->belongsToMany(Unit::class)->withTimestamps();
    }

    /**
     * Admins see every employee; managers only employees of the units they manage.
     *
     * @param  Builder<Employee>  $query
     */
    public function scopeVisibleTo(Builder $query, User $user): void
    {
        if ($user->isOrganizationAdmin()) {
            return;
        }

        $query->whereHas('units.managers', fn (Builder $managers) => $managers->whereKey($user->id));
    }
}
