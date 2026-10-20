{{-- Top of the side menu: switch between Sri Lakshmi Chit Funds, Sri Lakshmi Traders and
     Sri Lakshmi Micro Finance. --}}

@php
    $current = $business['key'] ?? 'chit';
@endphp

<div class="business-switch business-switch-three" role="group" aria-label="Business">

    @foreach ([
        'chit' => ['dashboard', 'layers', 'Sri Lakshmi', 'Chit Funds'],
        'traders' => ['traders.dashboard', 'package', 'Sri Lakshmi', 'Traders'],
        'finance' => ['finance.dashboard', 'wallet', 'Sri Lakshmi', 'Micro Finance'],
    ] as $key => [$home, $icon, $prefix, $name])
        <a
            href="{{ route($home) }}"
            @class(['business-switch-option', 'active' => $current === $key])
            @if ($current === $key) aria-current="true" @endif
        >
            <x-icon :name="$icon" />
            <span><span class="business-switch-sn">{{ $prefix }}</span> {{ $name }}</span>
        </a>
    @endforeach

</div>
