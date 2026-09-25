{{-- What a printed / PDF payment list covers: the dates (or the receipt
     looked up), the group / method / search used, and the totals. --}}

@php
    $rangeText = $filters['receipt_lookup']
        ? __('Receipt').' '.strtoupper($filters['q'])
        : ($filters['from'] === $filters['to']
            ? \Illuminate\Support\Carbon::parse($filters['from'])->format('D, d M Y')
            : \Illuminate\Support\Carbon::parse($filters['from'])->format('d M Y').' – '.\Illuminate\Support\Carbon::parse($filters['to'])->format('d M Y'));

    $filterText = collect([
        $filters['group_name'] ? __('Group').': '.$filters['group_name'] : null,
        $filters['method'] !== '' ? __('Method').': '.__(\App\Models\Payment::METHODS[$filters['method']]) : null,
        $filters['q'] !== '' && ! $filters['receipt_lookup'] ? __('Search').': “'.$filters['q'].'”' : null,
    ])->filter()->implode(' · ');
@endphp

<table class="info">
    <tr>
        <td><span class="label">{{ __('Period') }}</span><strong>{{ $rangeText }}</strong></td>
        <td><span class="label">{{ __('Receipts') }}</span><strong>{{ $summary['count'] }}</strong></td>
        <td><span class="label">{{ __('Total collected') }}</span><strong><x-rupees :amount="$summary['total']" /></strong></td>
        @foreach ($summary['by_method'] as $methodKey => $methodTotal)
            <td>
                <span class="label">{{ \App\Models\Payment::METHODS[$methodKey] }}</span>
                <strong><x-rupees :amount="$methodTotal['amount']" /></strong>
                <span class="muted">({{ $methodTotal['count'] }})</span>
            </td>
        @endforeach
    </tr>
    @if ($filterText !== '')
        <tr>
            <td colspan="{{ 3 + count($summary['by_method']) }}" class="muted">{{ $filterText }}</td>
        </tr>
    @endif
</table>

@if ($summary['count'] > $limit)
    <p class="warning">{{ __('Showing the first :shown of :total — narrow the dates or filters to print the rest.', ['shown' => number_format($limit), 'total' => number_format($summary['count'])]) }}</p>
@endif
