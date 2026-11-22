<?php

namespace App\Models\Concerns;

use App\Models\Organization;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

/**
 * Limits every query to the signed-in user's current organization and fills
 * organization_id on create. Without a signed-in user (console, queue, seeders)
 * queries are not scoped, so jobs must filter by organization themselves.
 *
 * @mixin Model
 */
trait BelongsToOrganization
{
    public static function bootBelongsToOrganization(): void
    {
        static::addGlobalScope('organization', function (Builder $builder): void {
            if (! Auth::check()) {
                return;
            }

            $builder->where(
                $builder->qualifyColumn('organization_id'),
                Auth::user()->current_organization_id
            );
        });

        static::creating(function (Model $model): void {
            if (empty($model->organization_id) && Auth::check()) {
                $model->organization_id = Auth::user()->current_organization_id;
            }
        });
    }

    /**
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
