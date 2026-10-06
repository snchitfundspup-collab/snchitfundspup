@extends('layouts.app')

@section('title', 'Loan Statement – '.$customer->name.' | Sri Lakshmi Micro Finance')

@push('styles')
    @vite([
        'resources/css/groups.css',
        'resources/css/payments.css',
        'resources/css/traders.css',
        'resources/css/finance.css'
    ])
@endpush

@section('content')

<div class="group-show-page payments-page traders-page finance-page">

    @include('traders.partials.flash')

    <section class="group-hero glass">

        <a href="{{ route('finance.reports.customer') }}" class="group-back-link">
            <x-icon name="arrow-left" />
            <span data-i18n="customer_statement">Customer Statement</span>
        </a>

        <div class="group-hero-main">
            <x-customer-avatar :customer="$customer" class="payment-customer-avatar" />
            <div class="group-hero-text">
                <h1 class="group-hero-title"><x-customer-name :customer="$customer" /></h1>
                <p class="group-hero-meta">{{ $customer->customer_code }} · {{ $customer->phone ?: '—' }}</p>
            </div>
            <div class="group-hero-actions">
                <a href="{{ route('finance.loans.create', ['customer' => $customer->id]) }}" class="add-group-button">
                    <x-icon name="plus" />
                    <span data-i18n="new_loan">New Loan</span>
                </a>
                <a href="{{ route('finance.accounts.print', $customer) }}" class="group-action" target="_blank" rel="noopener">
                    <x-icon name="printer" />
                    <span data-i18n="print_statement">Print statement</span>
                </a>
                <a href="{{ route('finance.accounts.pdf', $customer) }}" class="group-action download-pdf-button">
                    <x-icon name="download" />
                    <span data-i18n="download_pdf">Download PDF</span>
                </a>
            </div>
        </div>

    </section>


    <section class="payment-summary-bar glass dues-summary">
        <div class="payment-summary-main">
            <span class="payment-summary-range" data-i18n="balance_word">Balance</span>
            <strong @class(['payment-summary-total', 'dues-total-pending' => $totals['overdue'] > 0])><x-rupees :amount="$totals['balance']" /></strong>
            @if ($totals['overdue'] > 0)
                <span class="payment-summary-count ledger-total-due"><span data-i18n="overdue_word">Overdue</span> <x-rupees :amount="$totals['overdue']" /></span>
            @endif
        </div>
        <div class="payment-summary-main">
            <span class="payment-summary-range" data-i18n="collected_word">Collected</span>
            <strong class="payment-summary-total"><x-rupees :amount="$totals['collected']" /></strong>
            <span class="payment-summary-count"><span data-i18n="of_word">of</span> <x-rupees :amount="$totals['repayable']" /></span>
        </div>
    </section>


    @forelse ($loans as $row)
        @php [$loan, $standing] = [$row['loan'], $row['standing']]; @endphp

        <section class="group-panel glass payment-step">

            <div class="group-panel-header">
                <h2>
                    <a href="{{ route('finance.loans.show', $loan) }}" class="dues-member-link">{{ $loan->loan_number }}</a>
                    · {{ $loan->loaned_on->format('d M Y') }}
                </h2>
                @include('finance.partials.state-badge', ['state' => $standing['state']])
            </div>

            <div class="finance-standing">
                <span><small data-i18n="loan_amount_short">Loan amount</small><strong><x-rupees :amount="$loan->principal" /></strong></span>
                <span><small data-i18n="given_in_hand">Given in hand</small><strong><x-rupees :amount="$loan->amountGiven()" /></strong></span>
                <span><small data-i18n="total_to_repay">Total to repay</small><strong><x-rupees :amount="$loan->loan_amount" /></strong></span>
                <span><small data-i18n="repayment_word">Repayment</small><strong>{{ $loan->frequencyLabel() }} · <x-rupees :amount="$loan->installment_amount" /></strong></span>
                <span><small data-i18n="collected_word">Collected</small><strong class="ledger-total-paid"><x-rupees :amount="$loan->collected()" /></strong></span>
                <span><small data-i18n="balance_word">Balance</small><strong @class(['ledger-total-due' => $standing['overdue'] > 0])><x-rupees :amount="$loan->balance()" /></strong></span>
            </div>

            @if ($loan->collections->isNotEmpty())
                <div class="ledger-table-wrapper">
                    <table class="ledger-table statement-table portal-table">
                        <thead>
                            <tr>
                                <th data-i18n="date_time">Date &amp; time</th>
                                <th data-i18n="receipt_no">Receipt No.</th>
                                <th class="ledger-col-total" data-i18n="amount_word">Amount</th>
                                <th class="ledger-col-total" data-i18n="balance_word">Balance</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $running = $loan->loan_amount; @endphp
                            @foreach ($loan->collections as $collection)
                                @php $running -= $collection->amount; @endphp
                                <tr>
                                    <td class="nowrap" data-label="Date">{{ $collection->collected_at->format('d M Y, h:i A') }}</td>
                                    <td data-label="Receipt"><a href="{{ route('finance.collections.show', $collection) }}" class="statement-receipt-link">{{ $collection->receipt_number }}</a></td>
                                    <td class="ledger-col-total ledger-total-paid" data-label="Amount"><x-rupees :amount="$collection->amount" /></td>
                                    <td class="ledger-col-total" data-label="Balance"><strong><x-rupees :amount="max(0, $running)" /></strong></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

        </section>
    @empty
        <div class="groups-empty glass">
            <span class="groups-empty-icon icon-3d icon-3d-green"><x-icon name="wallet" /></span>
            <strong data-i18n="no_loans">No loans here.</strong>
        </div>
    @endforelse

</div>

@endsection
