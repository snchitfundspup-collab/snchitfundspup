{{-- Top of the side menu: switch between SN Chit Funds and SN Traders. --}}

@php
    $inTraders = ($business['key'] ?? 'chit') === 'traders';
@endphp

<div class="business-switch" role="group" aria-label="Business">

    <a
        href="{{ route('dashboard') }}"
        @class(['business-switch-option', 'active' => ! $inTraders])
        @unless ($inTraders) aria-current="true" @endunless
    >
        <x-icon name="layers" />
        <span><span class="business-switch-sn">SN</span> Chit Funds</span>
    </a>

    <a
        href="{{ route('traders.dashboard') }}"
        @class(['business-switch-option', 'active' => $inTraders])
        @if ($inTraders) aria-current="true" @endif
    >
        <x-icon name="package" />
        <span><span class="business-switch-sn">SN</span> Traders</span>
    </a>

</div>
