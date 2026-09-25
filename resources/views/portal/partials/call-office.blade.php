{{-- "Call office" — the round green call button, for customers.
     $compact: icon only (header). --}}

@php
    $officePhone = (string) config('app.office_phone');
@endphp

@if ($officePhone !== '')
    <a
        href="tel:{{ preg_replace('/[^\d+]/', '', $officePhone) }}"
        @class(['member-call-button', 'portal-call-button', 'portal-call-compact' => $compact ?? false])
        aria-label="Call the office on {{ $officePhone }}"
        title="{{ $officePhone }}"
    >
        <x-icon name="phone" />
        <span data-i18n="call_office">Call office</span>
    </a>
@endif
