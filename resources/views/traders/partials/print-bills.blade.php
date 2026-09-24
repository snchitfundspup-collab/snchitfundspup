{{-- Sales or purchases list for the print page and PDF.
     $kind ('sale' | 'purchase'), $bills, $filters, $summary, $limit --}}

@php
    $isSale = $kind === 'sale';
    $rangeText = $filters['range'] === 'all'
        ? 'All time'
        : ($filters['from'] === $filters['to']
            ? \Illuminate\Support\Carbon::parse($filters['from'])->format('D, d M Y')
            : \Illuminate\Support\Carbon::parse($filters['from'])->format('d M Y').' – '.\Illuminate\Support\Carbon::parse($filters['to'])->format('d M Y'));
@endphp

<table class="info">
    <tr>
        <td><span class="label">Period</span><strong>{{ $rangeText }}</strong></td>
        <td><span class="label">{{ $isSale ? 'Invoices' : 'Purchases' }}</span><strong>{{ $summary['count'] }}</strong></td>
        <td><span class="label">Total</span><strong><x-rupees :amount="$summary['total']" /></strong></td>
        <td><span class="label">Rice</span><strong>{{ $summary['bags'] }} bags</strong></td>
        @if ($isSale)
            <td><span class="label">Received at sale</span><strong><x-rupees :amount="$summary['received']" /></strong></td>
        @endif
    </tr>
    @if (($filters['supplier_name'] ?? null) || $filters['q'] !== '')
        <tr>
            <td colspan="5" class="muted">
                {{ collect([($filters['supplier_name'] ?? null) ? 'Supplier: '.$filters['supplier_name'] : null, $filters['q'] !== '' ? 'Search: “'.$filters['q'].'”' : null])->filter()->implode(' · ') }}
            </td>
        </tr>
    @endif
</table>

@if ($summary['count'] > $limit)
    <p class="warning">Showing the first {{ number_format($limit) }} of {{ number_format($summary['count']) }} — narrow the dates or filters to print the rest.</p>
@endif

<table class="grid">
    <thead>
        <tr>
            <th>#</th>
            <th>Date</th>
            <th>{{ $isSale ? 'Invoice' : 'Purchase' }}</th>
            <th>{{ $isSale ? 'Customer' : 'Supplier' }}</th>
            <th>Rice</th>
            <th class="amount">Bags</th>
            @if ($isSale)
                <th class="amount">Received</th>
            @endif
            <th class="amount">Amount</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($bills as $bill)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td class="nowrap">{{ ($isSale ? $bill->sold_on : $bill->purchased_on)->format('d M Y') }}</td>
                <td class="nowrap">
                    {{ $isSale ? $bill->invoice_number : $bill->purchase_number }}
                    @if (! $isSale && $bill->supplier_bill_no)
                        <span class="code">Bill {{ $bill->supplier_bill_no }}</span>
                    @endif
                </td>
                <td>
                    @if ($isSale)
                        <strong>{{ $bill->customer->name }}</strong>
                        @if (filled($bill->customer->remarks))
                            <span class="ident">({{ $bill->customer->remarks }})</span>
                        @endif
                        <span class="code">{{ $bill->customer->customer_code }}</span>
                    @else
                        <strong>{{ $bill->supplier->name }}</strong>
                    @endif
                </td>
                <td>{{ $bill->items->map(fn ($item) => $item->variety->name.' '.$item->quantityLabel())->implode(', ') }}</td>
                <td class="amount">{{ $bill->items->sum('bags') }}</td>
                @if ($isSale)
                    <td class="amount"><x-rupees :amount="$bill->receipts->sum('amount')" /></td>
                @endif
                <td class="amount"><x-rupees :amount="$bill->total_amount" /></td>
            </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr>
            <th colspan="5">Total ({{ $bills->count() }})</th>
            <td class="amount">{{ $bills->sum(fn ($bill) => $bill->items->sum('bags')) }}</td>
            @if ($isSale)
                <td class="amount"><x-rupees :amount="$bills->sum(fn ($bill) => $bill->receipts->sum('amount'))" /></td>
            @endif
            <td class="amount"><x-rupees :amount="$bills->sum('total_amount')" /></td>
        </tr>
    </tfoot>
</table>

@if ($summary['by_variety']->isNotEmpty())
    <h3 class="list-title">By rice variety</h3>
    <table class="grid">
        <thead>
            <tr>
                <th>Variety</th>
                <th class="amount">Bags</th>
                <th class="amount">Bags</th>
                <th class="amount">Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($summary['by_variety'] as $byVariety)
                <tr>
                    <td>{{ $byVariety['name'] }}</td>
                    <td class="amount">{{ $byVariety['bags'] }}</td>
                    <td class="amount">{{ $byVariety['bags'] }}</td>
                    <td class="amount"><x-rupees :amount="$byVariety['amount']" /></td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endif
