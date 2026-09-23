@props(['status'])

@php
    /**
     * Coloured status pill for a chit group.
     */
    $labels = [
        'forming' => 'Forming',
        'running' => 'Running',
        'completed' => 'Completed',
    ];
@endphp

<span
    {{ $attributes->merge(['class' => "group-status group-status-{$status}"]) }}
    data-i18n="status_{{ $status }}"
>{{ $labels[$status] ?? ucfirst($status) }}</span>
