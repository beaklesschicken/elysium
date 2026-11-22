<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * A scan or photo of a credential, kept on the private disk and served only through
 * an authorized controller.
 */
class CredentialDocument extends Model
{
    use BelongsToOrganization;

    public const DISK = 'local';

    protected $fillable = [
        'organization_id',
        'credential_id',
        'uploaded_by',
        'path',
        'original_name',
        'mime_type',
        'size',
    ];

    protected static function booted(): void
    {
        static::deleted(function (CredentialDocument $document): void {
            Storage::disk(self::DISK)->delete($document->path);
        });
    }

    /**
     * @return BelongsTo<Credential, $this>
     */
    public function credential(): BelongsTo
    {
        return $this->belongsTo(Credential::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function humanSize(): string
    {
        return $this->size >= 1048576
            ? round($this->size / 1048576, 1).' MB'
            : max(1, (int) round($this->size / 1024)).' KB';
    }
}
