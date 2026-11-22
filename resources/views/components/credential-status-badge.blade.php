@props([
    'status' => null,
    'empty' => false,
])

@if ($status)
    <flux:badge size="sm" :color="$status->color()" {{ $attributes }}>{{ $status->label() }}</flux:badge>
@elseif ($empty)
    <flux:text size="sm" {{ $attributes }}>{{ __('None yet') }}</flux:text>
@endif
