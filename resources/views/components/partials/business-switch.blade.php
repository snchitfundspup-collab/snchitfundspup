{{-- Top of the side menu: switch between SN Chit Funds, SN Traders and
     Sri Lakshmi Micro Finance. --}}

@php
    $current = $business['key'] ?? 'chit';
@endphp

<div class="business-switch business-switch-three" role="group" aria-label="Business">

    @foreach ([
        'chit' => ['dashboard', 'layers', 'SN', 'Chit Funds'],
        'traders' => ['traders.dashboard', 'package', 'SN', 'Traders'],
        'finance' => ['finance.dashboard', 'wallet', 'Sri Lakshmi', 'Micro Finance'],
    ] as $key => [$home, $icon, $prefix, $name])
        <a
            href="{{ route($home, $key === 'chit' && \App\Http\Middleware\SetBusinessContext::onFinanceDomain(request()) ? ['chit' => 1] : []) }}"
            @class(['business-switch-option', 'active' => $current === $key])
            @if ($current === $key) aria-current="true" @endif
        >
            <x-icon :name="$icon" />
            <span><span class="business-switch-sn">{{ $prefix }}</span> {{ $name }}</span>
        </a>
    @endforeach

</div>
