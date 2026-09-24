{{-- The filtered payments as one table (print page and PDF). --}}

<table class="grid">

    <thead>
        <tr>
            <th>#</th>
            <th>Date &amp; time</th>
            <th>Receipt</th>
            <th>Customer</th>
            <th>Group</th>
            <th>Month</th>
            <th>Method</th>
            <th class="amount">Amount</th>
        </tr>
    </thead>

    <tbody>
        @foreach ($payments as $payment)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td class="nowrap">{{ $payment->paid_at->format('d M Y, h:i A') }}</td>
                <td class="nowrap">{{ $payment->receipt_number }}</td>
                <td>
                    <strong>{{ $payment->customer->name }}</strong>
                    @if (filled($payment->customer->remarks))
                        <span class="ident">({{ $payment->customer->remarks }})</span>
                    @endif
                    <span class="code">{{ $payment->member->member_code }}</span>
                </td>
                <td>{{ $payment->chitGroup->name }}</td>
                <td class="nowrap">{{ $payment->monthsCoveredLabel() }}</td>
                <td>{{ $payment->methodLabel() }}</td>
                <td class="amount"><x-rupees :amount="$payment->amount" /></td>
            </tr>
        @endforeach
    </tbody>

    <tfoot>
        <tr>
            <th colspan="7">Total ({{ $payments->count() }} receipts)</th>
            <td class="amount"><x-rupees :amount="$payments->sum('amount')" /></td>
        </tr>
    </tfoot>

</table>
