{{-- Expense list for the print page and PDF: period, totals by partner,
     then every expense. --}}

@php
    $rangeText = $filters['range'] === 'all'
        ? __('All time')
        : ($filters['from'] === $filters['to']
            ? \Illuminate\Support\Carbon::parse($filters['from'])->format('D, d M Y')
            : \Illuminate\Support\Carbon::parse($filters['from'])->format('d M Y').' – '.\Illuminate\Support\Carbon::parse($filters['to'])->format('d M Y'));

    $filterText = collect([
        $filters['partner_name'] ? __('Paid by').': '.$filters['partner_name'] : null,
        $filters['q'] !== '' ? __('Search').': “'.$filters['q'].'”' : null,
    ])->filter()->implode(' · ');
@endphp

<table class="info">
    <tr>
        <td><span class="label">{{ __('Period') }}</span><strong>{{ $rangeText }}</strong></td>
        <td><span class="label">{{ __('Expenses') }}</span><strong>{{ $summary['count'] }}</strong></td>
        <td><span class="label">{{ __('Total spent') }}</span><strong><x-rupees :amount="$summary['total']" /></strong></td>
        @foreach ($summary['by_partner'] as $byPartner)
            <td>
                <span class="label">{{ __('Paid by') }} {{ $byPartner['name'] }}</span>
                <strong><x-rupees :amount="$byPartner['amount']" /></strong>
                <span class="muted">({{ $byPartner['count'] }})</span>
            </td>
        @endforeach
    </tr>
    @if ($filterText !== '')
        <tr>
            <td colspan="{{ 3 + $summary['by_partner']->count() }}" class="muted">{{ $filterText }}</td>
        </tr>
    @endif
</table>

@if ($summary['count'] > $limit)
    <p class="warning">{{ __('Showing the first :shown of :total — narrow the dates or filters to print the rest.', ['shown' => number_format($limit), 'total' => number_format($summary['count'])]) }}</p>
@endif

<table class="grid">
    <thead>
        <tr>
            <th>#</th>
            <th>{{ __('Date') }}</th>
            <th>{{ __('What for') }}</th>
            <th>{{ __('Paid to') }}</th>
            <th>{{ __('Bill no.') }}</th>
            <th>{{ __('Paid by') }}</th>
            <th>{{ __('Method') }}</th>
            <th class="amount">{{ __('Amount') }}</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($expenses as $expense)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td class="nowrap">{{ $expense->spent_on->format('d M Y') }}</td>
                <td>
                    {{ $expense->description }}
                    @if ($expense->notes)
                        <span class="ident">({{ $expense->notes }})</span>
                    @endif
                </td>
                <td>{{ $expense->paid_to ?: '—' }}</td>
                <td class="nowrap">{{ $expense->reference ?: '—' }}</td>
                <td>{{ $expense->payer->name }}</td>
                <td>{{ $expense->methodLabel() }}</td>
                <td class="amount"><x-rupees :amount="$expense->amount" /></td>
            </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr>
            <th colspan="7">{{ __('Total') }} ({{ $expenses->count() }})</th>
            <td class="amount"><x-rupees :amount="$expenses->sum('amount')" /></td>
        </tr>
    </tfoot>
</table>

