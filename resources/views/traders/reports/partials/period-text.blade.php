{{-- Plain-text period for printed reports ($filters). --}}
@if ($filters['range'] === 'all')
    {{ __('All time') }}
@elseif ($filters['from'] === $filters['to'])
    {{ \Illuminate\Support\Carbon::parse($filters['from'])->format('D, d M Y') }}
@else
    {{ \Illuminate\Support\Carbon::parse($filters['from'])->format('d M Y') }} – {{ \Illuminate\Support\Carbon::parse($filters['to'])->format('d M Y') }}
@endif
