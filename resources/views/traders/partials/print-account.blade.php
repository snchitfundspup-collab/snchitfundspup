{{-- A customer's SN Traders account for the PDF: every invoice and
     receipt with a running balance. --}}

<table class="info">
    <tr>
        <td>
            <span class="label">Customer</span>
            <strong>{{ $customer->name }}</strong>
            @if (filled($customer->remarks))
                <span class="ident">({{ $customer->remarks }})</span>
            @endif
        </td>
        <td><span class="label">Customer ID</span><strong>{{ $customer->customer_code }}</strong></td>
        <td><span class="label">Phone</span><strong>{{ $customer->phone ?: '—' }}</strong></td>
        <td><span class="label">As on</span><strong>{{ $today->format('d M Y') }}</strong></td>
    </tr>
    <tr>
        <td><span class="label">Total sales</span><strong><x-rupees :amount="$totalSales" /></strong></td>
        <td><span class="label">Total received</span><strong><x-rupees :amount="$totalReceived" /></strong></td>
        <td colspan="2">
            <span class="label">{{ $balance < 0 ? 'Advance' : 'Balance due' }}</span>
            <strong class="{{ $balance > 0 ? 'pending' : '' }}"><x-rupees :amount="abs($balance)" /></strong>
        </td>
    </tr>
</table>

<table class="grid">
    <thead>
        <tr>
            <th>Date</th>
            <th>Entry</th>
            <th>Details</th>
            <th class="amount">Sale</th>
            <th class="amount">Received</th>
            <th class="amount">Balance</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($entries as $entry)
            <tr>
                <td class="nowrap">{{ \Illuminate\Support\Carbon::parse($entry['date'])->format('d M Y') }}</td>
                <td class="nowrap">{{ $entry['type'] === 'sale' ? 'Invoice' : 'Receipt' }} {{ $entry['number'] }}</td>
                <td>{{ $entry['details'] }}</td>
                <td class="amount">@if ($entry['debit'] > 0)<x-rupees :amount="$entry['debit']" />@endif</td>
                <td class="amount">@if ($entry['credit'] > 0)<x-rupees :amount="$entry['credit']" />@endif</td>
                <td class="amount"><x-rupees :amount="$entry['balance']" /></td>
            </tr>
        @empty
            <tr><td colspan="6" class="muted">No sales or receipts yet.</td></tr>
        @endforelse
    </tbody>
    <tfoot>
        <tr>
            <th colspan="3">Total</th>
            <td class="amount"><x-rupees :amount="$totalSales" /></td>
            <td class="amount"><x-rupees :amount="$totalReceived" /></td>
            <td class="amount"><x-rupees :amount="$balance" /></td>
        </tr>
    </tfoot>
</table>
