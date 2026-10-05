{{-- Outstanding Loans for the print page and PDF. --}}

<table class="info">
    <tr>
        <td><span class="label">{{ __('As on') }}</span><strong>{{ $today->format('d M Y') }}</strong></td>
        <td><span class="label">{{ __('Running loans') }}</span><strong>{{ $summary['loans'] }} · {{ $summary['customers'] }} {{ __('customers') }}</strong></td>
        <td><span class="label">{{ __('Money with customers') }}</span><strong><x-rupees :amount="$summary['balance']" /></strong></td>
        <td><span class="label">{{ __('Overdue') }}</span><strong class="pending"><x-rupees :amount="$summary['overdue']" /></strong></td>
    </tr>
</table>

<table class="grid">
    <thead>
        <tr>
            <th>{{ __('Customer') }}</th>
            <th>{{ __('Loan No.') }}</th>
            <th>{{ __('Loan date') }}</th>
            <th>{{ __('Instalment') }}</th>
            <th class="amount">{{ __('Total to repay') }}</th>
            <th class="amount">{{ __('Collected') }}</th>
            <th class="amount">{{ __('Balance') }}</th>
            <th class="amount">{{ __('Overdue') }}</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($rows as $row)
            @php [$loan, $standing] = [$row['loan'], $row['standing']]; @endphp
            <tr>
                <td>{{ $loan->customer->name }}@if (filled($loan->customer->remarks)) ({{ $loan->customer->remarks }})@endif<br><span class="muted">{{ $loan->customer->customer_code }} · {{ $loan->customer->phone }}</span></td>
                <td>{{ $loan->loan_number }}</td>
                <td>{{ $loan->loaned_on->format('d M Y') }}</td>
                <td>{{ $loan->frequencyLabel() }} <x-rupees :amount="$loan->installment_amount" /></td>
                <td class="amount"><x-rupees :amount="$loan->loan_amount" /></td>
                <td class="amount"><x-rupees :amount="$loan->collected()" /></td>
                <td class="amount"><strong><x-rupees :amount="$loan->balance()" /></strong></td>
                <td class="amount {{ $standing['overdue'] > 0 ? 'pending' : '' }}">@if ($standing['overdue'] > 0)<x-rupees :amount="$standing['overdue']" />@else — @endif</td>
            </tr>
        @empty
            <tr><td colspan="8">{{ __('No running loans.') }}</td></tr>
        @endforelse
    </tbody>
    @if ($rows->isNotEmpty())
        <tfoot>
            <tr>
                <th colspan="4">{{ __('Total') }}</th>
                <td class="amount"><x-rupees :amount="$summary['repayable']" /></td>
                <td class="amount"><x-rupees :amount="$summary['collected']" /></td>
                <td class="amount"><strong><x-rupees :amount="$summary['balance']" /></strong></td>
                <td class="amount pending"><x-rupees :amount="$summary['overdue']" /></td>
            </tr>
        </tfoot>
    @endif
</table>
