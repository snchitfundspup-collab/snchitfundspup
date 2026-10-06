{{-- Overdue Loans for the print page and PDF. --}}

<table class="info">
    <tr>
        <td><span class="label">{{ __('As on') }}</span><strong>{{ $today->format('d M Y') }}</strong></td>
        <td><span class="label">{{ __('Loans') }}</span><strong>{{ $summary['loans'] }}</strong></td>
        <td><span class="label">{{ __('Overdue') }}</span><strong class="pending"><x-rupees :amount="$summary['overdue']" /></strong></td>
        <td><span class="label">{{ __('Balance') }}</span><strong><x-rupees :amount="$summary['balance']" /></strong></td>
    </tr>
</table>

<table class="grid">
    <thead>
        <tr>
            <th>{{ __('Customer') }}</th>
            <th>{{ __('Loan No.') }}</th>
            <th>{{ __('Instalment') }}</th>
            <th>{{ __('Overdue since') }}</th>
            <th class="amount">{{ __('days late') }}</th>
            <th>{{ __('Last paid') }}</th>
            <th class="amount">{{ __('Overdue') }}</th>
            <th class="amount">{{ __('Balance') }}</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($rows as $row)
            @php [$loan, $standing] = [$row['loan'], $row['standing']]; @endphp
            <tr>
                <td>{{ $loan->customer->name }}@if (filled($loan->customer->remarks)) ({{ $loan->customer->remarks }})@endif<br><span class="muted">{{ $loan->customer->customer_code }} · {{ $loan->customer->phone }}</span></td>
                <td>{{ $loan->loan_number }}</td>
                <td>{{ $loan->frequencyLabel() }} <x-rupees :amount="$loan->installment_amount" /></td>
                <td>{{ $standing['overdue_since']->format('d M Y') }}</td>
                <td class="amount pending">{{ $standing['days_overdue'] }}</td>
                <td>{{ $row['last_paid'] ? \Illuminate\Support\Carbon::parse($row['last_paid'])->format('d M Y') : '—' }}</td>
                <td class="amount pending"><strong><x-rupees :amount="$standing['overdue']" /></strong></td>
                <td class="amount"><x-rupees :amount="$loan->balance()" /></td>
            </tr>
        @empty
            <tr><td colspan="8">{{ __('Nothing is overdue.') }}</td></tr>
        @endforelse
    </tbody>
</table>
