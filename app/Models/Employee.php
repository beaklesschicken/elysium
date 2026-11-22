<?php

namespace App\Models;

use App\Enums\EmployeeStatus;
use App\Models\Concerns\BelongsToOrganization;
use App\Models\Concerns\HasCredentials;
use Database\Factories\EmployeeFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * A person whose credentials (and later tips) are tracked. Employees do not log in.
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
