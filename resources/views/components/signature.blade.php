{{-- The signature of the staff member who recorded a document (receipts,
     invoices, bills, vouchers), embedded as an image so it is never
     reachable by a public URL. Nothing when that person has no signature
     on file (resources/signatures/{username}.png). --}}
@props(['user' => null, 'height' => 40])

@php
    $signature = $user instanceof \App\Models\User ? $user->signatureDataUri() : null;
@endphp

@if ($signature)
    <img
        src="{{ $signature }}"
        alt="Signature of {{ $user->name }}"
        {{ $attributes->merge(['class' => 'signature-img', 'style' => "height: {$height}px; width: auto;"]) }}
    >
@endif
