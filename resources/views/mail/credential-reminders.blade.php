<x-mail::message>
# {{ __('Credentials needing attention') }}

{{ __('These permits, trainings or certificates at :organization have expired or will expire soon.', ['organization' => $organization->name]) }}

<x-mail::table>
| {{ __('Who') }} | {{ __('Credential') }} | {{ __('Expires') }} |
|:--|:--|:--|
@foreach ($rows as $row)
| {{ $row['holder'] }} | {{ $row['credential'] }} | {{ $row['expires'] }} ({{ $row['when'] }}) |
@endforeach
</x-mail::table>

<x-mail::button :url="$url">
{{ __('Review credentials') }}
</x-mail::button>

<small>{{ __('An owner or admin can turn these emails off in organization details:') }} {{ $settingsUrl }}</small>

{{ config('app.name') }}
</x-mail::message>
