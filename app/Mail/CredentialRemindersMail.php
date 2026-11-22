<?php

namespace App\Mail;

use App\Models\Credential;
use App\Models\Organization;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class CredentialRemindersMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  Collection<int, Credential>  $credentials
     */
    public function __construct(
        public Organization $organization,
        public Collection $credentials,
    ) {}

    public function envelope(): Envelope
    {
        $expired = $this->credentials->filter(fn (Credential $credential) => $credential->daysUntilExpiry() < 0)->count();

        return new Envelope(
            subject: $expired > 0
                ? __(':organization: :count credential(s) expired or expiring', ['organization' => $this->organization->name, 'count' => $this->credentials->count()])
                : __(':organization: :count credential(s) expiring soon', ['organization' => $this->organization->name, 'count' => $this->credentials->count()]),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.credential-reminders',
            with: [
                'rows' => $this->credentials->map(fn (Credential $credential) => [
                    'holder' => str_replace('|', '/', $credential->holder?->name ?? '—'),
                    'credential' => str_replace('|', '/', $credential->type->name),
                    'expires' => $credential->expires_on->format('M j, Y'),
                    'when' => match (true) {
                        $credential->daysUntilExpiry() < 0 => __('expired'),
                        $credential->daysUntilExpiry() === 0 => __('today'),
                        default => trans_choice('in :count day|in :count days', $credential->daysUntilExpiry()),
                    },
                ]),
                'url' => route('credentials.index'),
                'settingsUrl' => route('organization.edit'),
            ],
        );
    }
}
