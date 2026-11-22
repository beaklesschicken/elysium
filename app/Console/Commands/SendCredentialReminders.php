<?php

namespace App\Console\Commands;

use App\Enums\EmployeeStatus;
use App\Enums\OrganizationRole;
use App\Mail\CredentialRemindersMail;
use App\Models\Credential;
use App\Models\CredentialReminder;
use App\Models\Employee;
use App\Models\Organization;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Mail;

/**
 * Emails one summary per person for organizations that turned reminders on.
 * Each credential is reminded once per threshold; when several thresholds are due at once
 * (for example the first run after turning reminders on) only one email covers them.
 */
class SendCredentialReminders extends Command
{
    protected $signature = 'credentials:send-reminders {--dry-run : List what would be sent without sending or recording anything}';

    protected $description = 'Email expiration reminders for organizations that turned them on';

    public function handle(): int
    {
        $organizations = Organization::query()->where('credential_reminders_enabled', true)->get();

        foreach ($organizations as $organization) {
            $this->remindFor($organization);
        }

        return self::SUCCESS;
    }

    private function remindFor(Organization $organization): void
    {
        $due = $this->dueCredentials($organization);

        if ($due->isEmpty()) {
            return;
        }

        $sentCount = 0;

        foreach ($this->recipients($organization) as $recipient) {
            $items = $due->filter(fn (Credential $credential) => $this->recipientCovers($recipient['user'], $recipient['role'], $recipient['unitIds'], $credential));

            if ($items->isEmpty()) {
                continue;
            }

            if ($this->option('dry-run')) {
                $this->line("{$organization->name}: {$recipient['user']->email} would get {$items->count()} reminder(s)");

                continue;
            }

            Mail::to($recipient['user'])->send(new CredentialRemindersMail($organization, $items->values()));
            $sentCount++;
        }

        if ($this->option('dry-run')) {
            return;
        }

        $now = now();
        foreach ($due as $credential) {
            foreach ($this->dueThresholds($credential) as $days) {
                CredentialReminder::query()->firstOrCreate(
                    ['credential_id' => $credential->id, 'days_before' => $days],
                    ['sent_at' => $now],
                );
            }
        }

        $this->info("{$organization->name}: {$due->count()} credential(s), {$sentCount} email(s)");
    }

    /**
     * Current credentials with at least one reached threshold that hasn't been sent.
     *
     * @return Collection<int, Credential>
     */
    private function dueCredentials(Organization $organization): Collection
    {
        return Credential::query()
            ->where('organization_id', $organization->id)
            ->current()
            ->whereNotNull('expires_on')
            ->whereDate('expires_on', '<=', today()->addDays(max(Organization::REMINDER_DAYS)))
            ->with(['type', 'holder', 'reminders'])
            ->orderBy('expires_on')
            ->get()
            ->filter(function (Credential $credential): bool {
                $sent = $credential->reminders->pluck('days_before')->all();

                return array_diff($this->dueThresholds($credential), $sent) !== [];
            })
            ->filter(fn (Credential $credential) => $credential->holder !== null && ! ($credential->holder instanceof Employee && $credential->holder->status !== EmployeeStatus::Active))
            ->values();
    }

    /**
     * @return list<int>
     */
    private function dueThresholds(Credential $credential): array
    {
        $daysLeft = $credential->daysUntilExpiry();

        return array_values(array_filter(Organization::REMINDER_DAYS, fn (int $days) => $daysLeft <= $days));
    }

    /**
     * @return list<array{user: User, role: OrganizationRole, unitIds: list<int>}>
     */
    private function recipients(Organization $organization): array
    {
        $members = $organization->users()->get();
        $managedUnitIds = Unit::query()
            ->where('organization_id', $organization->id)
            ->with('managers:users.id')
            ->get()
            ->flatMap(fn (Unit $unit) => $unit->managers->map(fn (User $manager) => [$manager->id, $unit->id]))
            ->groupBy(0)
            ->map(fn ($pairs) => $pairs->pluck(1)->all());

        return $members
            ->map(fn (User $member) => [
                'user' => $member,
                'role' => OrganizationRole::from($member->pivot->role),
                'unitIds' => $managedUnitIds->get($member->id, []),
            ])
            ->all();
    }

    /**
     * @param  list<int>  $unitIds
     */
    private function recipientCovers(User $user, OrganizationRole $role, array $unitIds, Credential $credential): bool
    {
        if ($role->canManageOrganization()) {
            return true;
        }

        $holder = $credential->holder;

        return match (true) {
            $holder instanceof Unit => in_array($holder->id, $unitIds, true),
            $holder instanceof Employee => $holder->units()->whereIn('units.id', $unitIds)->exists(),
            default => false,
        };
    }
}
