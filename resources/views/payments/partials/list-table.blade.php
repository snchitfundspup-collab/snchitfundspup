{{-- The filtered payments as one table (print page and PDF). --}}

<table class="grid">

    <thead>
        <tr>
            <th>#</th>
            <th>{{ __('Date & time') }}</th>
            <th>{{ __('Receipt') }}</th>
            <th>{{ __('Customer') }}</th>
            <th>{{ __('Group') }}</th>
            <th>{{ __('Month') }}</th>
            <th>{{ __('Method') }}</th>
            <th class="amount">{{ __('Amount') }}</th>
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
            <th colspan="7">{{ __('Total') }} ({{ $payments->count() }})</th>
            <td class="amount"><x-rupees :amount="$payments->sum('amount')" /></td>
        </tr>
    </tfoot>

</table>
