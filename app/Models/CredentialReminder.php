<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Remembers which expiry reminders went out, so each threshold is sent once per credential.
 */
class CredentialReminder extends Model
{
    public $timestamps = false;

    protected $fillable = ['credential_id', 'days_before', 'sent_at'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'days_before' => 'integer',
            'sent_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Credential, $this>
     */
    public function credential(): BelongsTo
    {
        return $this->belongsTo(Credential::class);
    }
}
