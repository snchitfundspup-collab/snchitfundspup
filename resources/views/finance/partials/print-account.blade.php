{{-- A customer's loans and collections, for the print page and PDF. --}}

<table class="info">
    <tr>
        <td><span class="label">{{ __('Customer') }}</span><strong>{{ $customer->name }}@if (filled($customer->remarks)) ({{ $customer->remarks }})@endif</strong></td>
        <td><span class="label">{{ __('Customer ID') }}</span><strong>{{ $customer->customer_code }}</strong></td>
        <td><span class="label">{{ __('Phone') }}</span><strong>{{ $customer->phone ?: '—' }}</strong></td>
        <td><span class="label">{{ __('Balance') }}</span><strong class="{{ $totals['overdue'] > 0 ? 'pending' : '' }}"><x-rupees :amount="$totals['balance']" /></strong></td>
    </tr>
</table>

@forelse ($loans as $row)
    @php [$loan, $standing] = [$row['loan'], $row['standing']]; @endphp

    <h3 class="list-title">{{ __('Loan') }} {{ $loan->loan_number }} · {{ $loan->loaned_on->format('d M Y') }}</h3>

    <table class="grid">
        <tr>
            <td>{{ __('Loan amount') }}: <strong><x-rupees :amount="$loan->principal" /></strong></td>
            <td>{{ __('Given in hand') }}: <x-rupees :amount="$loan->amountGiven()" /></td>
            <td>{{ __('Total to repay') }}: <x-rupees :amount="$loan->loan_amount" /></td>
            <td>{{ __('Repayment') }}: {{ $loan->frequencyLabel() }} <x-rupees :amount="$loan->installment_amount" /></td>
            <td>{{ __('Collected') }}: <x-rupees :amount="$loan->collected()" /></td>
            <td class="{{ $standing['overdue'] > 0 ? 'pending' : '' }}">{{ __('Balance') }}: <strong><x-rupees :amount="$loan->balance()" /></strong>@if ($standing['overdue'] > 0) · {{ __('Overdue') }} <x-rupees :amount="$standing['overdue']" />@endif</td>
        </tr>
    </table>

    @if ($loan->collections->isNotEmpty())
        <table class="grid">
            <thead>
                <tr>
                    <th>{{ __('Date & time') }}</th>
                    <th>{{ __('Receipt No.') }}</th>
                    <th class="amount">{{ __('Amount') }}</th>
                    <th class="amount">{{ __('Balance') }}</th>
                </tr>
            </thead>
            <tbody>
                @php $running = $loan->loan_amount; @endphp
                @foreach ($loan->collections as $collection)
                    @php $running -= $collection->amount; @endphp
                    <tr>
                        <td>{{ $collection->collected_at->format('d M Y, h:i A') }}</td>
                        <td>{{ $collection->receipt_number }}</td>
                        <td class="amount"><x-rupees :amount="$collection->amount" /></td>
                        <td class="amount"><x-rupees :amount="max(0, $running)" /></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
@empty
    <p>{{ __('No loans here.') }}</p>
@endforelse
