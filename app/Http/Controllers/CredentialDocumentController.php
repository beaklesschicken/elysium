<?php

namespace App\Http\Controllers;

use App\Models\Credential;
use App\Models\CredentialDocument;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CredentialDocumentController extends Controller
{
    /**
     * Shows a credential's document to people who can see the credential.
     */
    public function __invoke(Credential $credential, CredentialDocument $document): StreamedResponse
    {
        abort_unless($document->credential_id === $credential->id, 404);
        Gate::authorize('view', $credential);

        $disk = Storage::disk(CredentialDocument::DISK);
        abort_unless($disk->exists($document->path), 404);

        $inline = str_starts_with($document->mime_type, 'image/') || $document->mime_type === 'application/pdf';

        return $disk->response($document->path, $document->original_name, [
            'Content-Type' => $document->mime_type,
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ], $inline ? 'inline' : 'attachment');
    }
}
