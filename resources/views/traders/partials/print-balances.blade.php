{{-- Customer balances for the print page and PDF. --}}

<table class="info">
    <tr>
        <td><span class="label">As on</span><strong>{{ $today->format('D, d M Y') }}</strong></td>
        <td><span class="label">Customers owe</span><strong class="pending"><x-rupees :amount="$totalOwing" /></strong> <span class="muted">({{ $owingCount }})</span></td>
        @if ($totalAdvance > 0)
            <td><span class="label">Advances held</span><strong><x-rupees :amount="$totalAdvance" /></strong></td>
        @endif
        <td><span class="label">Showing</span><strong>{{ ['owing' => 'Customers who owe', 'advance' => 'Advances', 'all' => 'All customers'][$view] }}</strong></td>
    </tr>
</table>

<table class="grid">
    <thead>
        <tr>
            <th>#</th>
            <th>Customer</th>
            <th>Phone</th>
            <th>Last sale</th>
            <th class="amount">Sales</th>
            <th class="amount">Received</th>
            <th class="amount">Balance</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($rows as $row)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>
                    <strong>{{ $row['customer']->name }}</strong>
                    @if (filled($row['customer']->remarks))
                        <span class="ident">({{ $row['customer']->remarks }})</span>
                    @endif
                    <span class="code">{{ $row['customer']->customer_code }}</span>
                </td>
                <td class="nowrap">{{ $row['customer']->phone ?: '—' }}</td>
                <td class="nowrap">{{ $row['last_sale'] ? \Illuminate\Support\Carbon::parse($row['last_sale'])->format('d M Y') : '—' }}</td>
                <td class="amount"><x-rupees :amount="$row['sales']" /></td>
                <td class="amount"><x-rupees :amount="$row['received']" /></td>
                <td class="amount {{ $row['balance'] > 0 ? 'pending' : '' }}">
                    @if ($row['balance'] < 0)
                        <x-rupees :amount="-$row['balance']" /> advance
                    @else
                        <x-rupees :amount="$row['balance']" />
                    @endif
                </td>
            </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr>
            <th colspan="4">Total ({{ $rows->count() }})</th>
            <td class="amount"><x-rupees :amount="$rows->sum('sales')" /></td>
            <td class="amount"><x-rupees :amount="$rows->sum('received')" /></td>
            <td class="amount"><x-rupees :amount="$rows->sum('balance')" /></td>
        </tr>
    </tfoot>
</table>
