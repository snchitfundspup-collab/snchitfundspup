@props(['customer'])

@php
    /**
     * 3D initial thumbnail for a customer. The tone rotates through the
     * brand colors by customer id so neighbouring rows look different.
     */
    $tones = ['orange', 'blue', 'purple'];
    $tone = $tones[$customer->id % count($tones)];
    $initial = \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr(trim($customer->name), 0, 1)) ?: '?';
@endphp

<span
    {{ $attributes->merge(['class' => "customer-avatar avatar-3d icon-3d icon-3d-{$tone}"]) }}
    aria-hidden="true"
>{{ $initial }}</span>
