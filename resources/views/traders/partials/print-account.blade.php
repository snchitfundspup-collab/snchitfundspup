{{-- A customer's SN Traders account for the PDF: every invoice and
     receipt with a running balance. --}}

<table class="info">
    <tr>
        <td>
            <span class="label">{{ __('Customer') }}</span>
            <strong>{{ $customer->name }}</strong>
            @if (filled($customer->remarks))
                <span class="ident">({{ $customer->remarks }})</span>
            @endif
        </td>
        <td><span class="label">{{ __('Customer ID') }}</span><strong>{{ $customer->customer_code }}</strong></td>
        <td><span class="label">{{ __('Phone') }}</span><strong>{{ $customer->phone ?: '—' }}</strong></td>
        <td><span class="label">{{ __('As on') }}</span><strong>{{ $today->format('d M Y') }}</strong></td>
    </tr>
    <tr>
        <td><span class="label">{{ __('Total sales') }}</span><strong><x-rupees :amount="$totalSales" /></strong></td>
        <td><span class="label">{{ __('Total received') }}</span><strong><x-rupees :amount="$totalReceived" /></strong></td>
        <td colspan="2">
            <span class="label">{{ __($balance < 0 ? 'Advance' : 'Balance due') }}</span>
            <strong class="{{ $balance > 0 ? 'pending' : '' }}"><x-rupees :amount="abs($balance)" /></strong>
        </td>
    </tr>
</table>

<table class="grid">
    <thead>
        <tr>
            <th>{{ __('Date') }}</th>
            <th>{{ __('Entry') }}</th>
            <th>{{ __('Details') }}</th>
            <th class="amount">{{ __('Sale') }}</th>
            <th class="amount">{{ __('Received') }}</th>
            <th class="amount">{{ __('Balance') }}</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($entries as $entry)
            <tr>
                <td class="nowrap">{{ \Illuminate\Support\Carbon::parse($entry['date'])->format('d M Y') }}</td>
                <td class="nowrap">{{ __($entry['type'] === 'sale' ? 'Invoice' : 'Receipt') }} {{ $entry['number'] }}</td>
                <td>{{ $entry['details'] }}</td>
                <td class="amount">@if ($entry['debit'] > 0)<x-rupees :amount="$entry['debit']" />@endif</td>
                <td class="amount">@if ($entry['credit'] > 0)<x-rupees :amount="$entry['credit']" />@endif</td>
                <td class="amount"><x-rupees :amount="$entry['balance']" /></td>
            </tr>
        @empty
            <tr><td colspan="6" class="muted">{{ __('No sales or receipts yet.') }}</td></tr>
        @endforelse
    </tbody>
    <tfoot>
        <tr>
            <th colspan="3">{{ __('Total') }}</th>
            <td class="amount"><x-rupees :amount="$totalSales" /></td>
            <td class="amount"><x-rupees :amount="$totalReceived" /></td>
            <td class="amount"><x-rupees :amount="$balance" /></td>
        </tr>
    </tfoot>
</table>
