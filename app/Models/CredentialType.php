<?php

namespace App\Models;

use App\Enums\CredentialCategory;
use App\Enums\CredentialHolderType;
use Database\Factories\CredentialTypeFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

/**
 * A kind of credential that can be held. Rows without an organization are the shared public
 * catalog; rows with one are private to that organization.
 */
class CredentialType extends Model
{
    /** @use HasFactory<CredentialTypeFactory> */
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'name',
        'category',
        'holder_type',
        'issuing_authority',
        'us_state',
        'validity_months',
        'info_link',
        'description',
    ];

    /**
     * @return array<string, string|class-string>
     */
    protected function casts(): array
    {
        return [
            'category' => CredentialCategory::class,
            'holder_type' => CredentialHolderType::class,
            'validity_months' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::addGlobalScope('available', function (Builder $builder): void {
            if (! Auth::check()) {
                return;
            }

            $builder->where(fn (Builder $query) => $query
                ->whereNull($builder->qualifyColumn('organization_id'))
                ->orWhere($builder->qualifyColumn('organization_id'), Auth::user()->current_organization_id));
        });
    }

    /**
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * @return HasMany<Credential, $this>
     */
    public function credentials(): HasMany
    {
        return $this->hasMany(Credential::class);
    }

    public function isPublic(): bool
    {
        return $this->organization_id === null;
    }

    /**
     * @param  Builder<CredentialType>  $query
     */
    public function scopeForHolder(Builder $query, CredentialHolderType $holderType): void
    {
        $query->where('holder_type', $holderType);
    }

    public function defaultExpiryFrom(?Carbon $issuedOn): ?Carbon
    {
        if ($issuedOn === null || $this->validity_months === null) {
            return null;
        }

        return $issuedOn->copy()->addMonthsNoOverflow($this->validity_months);
    }
}
