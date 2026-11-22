<x-mail::message>
# {{ __('You\'re invited to :organization', ['organization' => $organizationName]) }}

{{ __(':inviter added you as :role', ['inviter' => $inviterName, 'role' => $roleLabel]) }}@if ($unitName) {{ __('for :unit', ['unit' => $unitName]) }}@endif. {{ __('You\'ll use :app to keep track of staff permits, trainings and certificates.', ['app' => config('app.name')]) }}

<x-mail::button :url="$acceptUrl">
{{ __('Accept invitation') }}
</x-mail::button>

{{ __('This link expires on :date. If you weren\'t expecting it, you can ignore this email.', ['date' => $expiresAt->format('F j, Y')]) }}

{{ config('app.name') }}
</x-mail::message>
