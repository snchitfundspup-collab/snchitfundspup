{{-- "01 Sep 2026 – 24 Sep 2026" / "Thu, 24 Sep 2026" / "All time" for $filters. --}}
@if ($filters['range'] === 'all')
    <span data-i18n="range_all">All time</span>
@elseif ($filters['from'] === $filters['to'])
    {{ \Illuminate\Support\Carbon::parse($filters['from'])->format('D, d M Y') }}
@else
    {{ \Illuminate\Support\Carbon::parse($filters['from'])->format('d M Y') }} – {{ \Illuminate\Support\Carbon::parse($filters['to'])->format('d M Y') }}
@endif
