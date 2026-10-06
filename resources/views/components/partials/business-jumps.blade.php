{{-- Dashboard buttons to open the other businesses ($business is the open one). --}}

@foreach ([
    'chit' => ['dashboard', 'layers', 'SN', 'Chit Funds'],
    'traders' => ['traders.dashboard', 'package', 'SN', 'Traders'],
    'finance' => ['finance.dashboard', 'wallet', 'Sri Lakshmi', 'Micro Finance'],
] as $key => [$home, $icon, $prefix, $name])
    @continue(($business['key'] ?? 'chit') === $key)
    <a href="{{ route($home) }}" class="dashboard-action business-jump">
        <x-icon :name="$icon" />
        <span><span class="business-switch-sn">{{ $prefix }}</span> {{ $name }}</span>
    </a>
@endforeach
