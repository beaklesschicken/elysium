<?php

namespace App\Models\Concerns;

use App\Enums\CredentialStatus;
use App\Models\Credential;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * For models that hold credentials: employees, units and organizations.
 *
 * @mixin Model
 */
trait HasCredentials
{
    /**
     * Every credential, including renewed (superseded) history.
     *
     * @return MorphMany<Credential, $this>
     */
    public function credentials(): MorphMany
    {
        return $this->morphMany(Credential::class, 'holder');
    }

    /**
     * @return MorphMany<Credential, $this>
     */
    public function currentCredentials(): MorphMany
    {
        return $this->credentials()->whereNull('superseded_at');
    }

    /**
     * The most urgent status among current credentials, or null when there are none.
     * Uses the loaded currentCredentials relation when present.
     */
    public function worstCredentialStatus(): ?CredentialStatus
    {
        return $this->currentCredentials
            ->map(fn (Credential $credential) => $credential->status())
            ->sortBy(fn (CredentialStatus $status) => $status->urgency())
            ->first();
    }
}
