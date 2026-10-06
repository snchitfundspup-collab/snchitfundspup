@extends('layouts.app')

@section('title', 'Loan '.$loan->loan_number.' | Sri Lakshmi Micro Finance')

@push('styles')
    @vite([
        'resources/css/create.css',
        'resources/css/groups.css',
        'resources/css/payments.css',
        'resources/css/traders.css',
        'resources/css/finance.css'
    ])
@endpush

@php
    $customer = $loan->customer;
@endphp

@section('content')

<div class="group-show-page payments-page traders-page finance-page">

    @include('traders.partials.flash')


    {{-- LOAN --}}

    <section class="group-hero glass">

        <a href="{{ route('finance.loans.index') }}" class="group-back-link">
            <x-icon name="arrow-left" />
            <span data-i18n="all_loans">All Loans</span>
        </a>

        <div class="group-hero-main">
            <span class="group-hero-icon icon-3d icon-3d-green">
                <x-icon name="wallet" />
            </span>
            <div class="group-hero-text">
                <div class="group-hero-tags">
                    <span class="group-type-label">{{ $loan->loan_number }}</span>
                    @include('finance.partials.state-badge', ['state' => $standing['state']])
                </div>
                <h1 class="group-hero-title"><x-customer-name :customer="$customer" /></h1>
                <p class="group-hero-meta">
                    {{ $customer->customer_code }} · {{ $customer->phone ?: '—' }}
                    · <span data-i18n="loan_date">Loan date</span> {{ $loan->loaned_on->format('d M Y') }}
                </p>
            </div>
            <div class="group-hero-actions">
                <a href="{{ route('finance.loans.acknowledgement', $loan) }}" class="group-action" target="_blank" rel="noopener">
                    <x-icon name="printer" />
                    <span data-i18n="print_acknowledgement">Print acknowledgement</span>
                </a>
                <a href="{{ route('finance.loans.acknowledgement.pdf', $loan) }}" class="group-action download-pdf-button">
                    <x-icon name="download" />
                    <span data-i18n="download_pdf">Download PDF</span>
                </a>
                <a href="{{ route('finance.loans.kfs', $loan) }}" class="group-action" target="_blank" rel="noopener">
                    <x-icon name="printer" />
                    <span data-i18n="kfs_passbook">KFS &amp; Passbook</span>
                </a>
                <a href="{{ route('finance.loans.kfs.pdf', $loan) }}" class="group-action download-pdf-button">
                    <x-icon name="download" />
                    <span data-i18n="kfs_passbook_pdf">KFS &amp; Passbook PDF</span>
                </a>
                <a href="{{ route('finance.accounts.show', $customer) }}" class="group-action">
                    <x-icon name="user-check" />
                    <span data-i18n="customer_statement">Customer Statement</span>
                </a>
                @if ($customer->phone)
                    <a href="tel:{{ preg_replace('/[^\d+]/', '', $customer->phone) }}" class="member-call-button" aria-label="Call {{ $customer->name }} on {{ $customer->phone }}">
                        <x-icon name="phone" />
                        <span data-i18n="call">Call</span>
                    </a>
                @endif
            </div>
        </div>

    </section>


    {{-- KEY FACTS --}}

    <section class="group-facts">
        @foreach ([
            ['wallet', 'green', 'loan_amount_short', 'Loan amount', $loan->principal],
            ['scale', 'purple', 'fee_and_gst', 'Fee + GST', $loan->charges()],
            ['rupee', 'blue', 'given_in_hand', 'Given in hand', $loan->amountGiven()],
            ['chart', 'purple', 'interest_word', 'Interest', $loan->interest],
            ['layers', 'orange', 'total_to_repay', 'Total to repay', $loan->loan_amount],
            ['calendar', 'orange', 'installment_word', 'Instalment', $loan->installment_amount],
            ['check', 'green', 'collected_word', 'Collected', $collectedTotal],
            ['info', 'red', 'balance_word', 'Balance', $loan->balance()],
        ] as [$factIcon, $factTone, $factKey, $factLabel, $factValue])
            <div class="group-fact glass">
                <span class="group-fact-icon icon-3d icon-3d-{{ $factTone }}">
                    <x-icon :name="$factIcon" />
                </span>
                <span class="group-fact-body">
                    <span class="group-fact-value"><x-rupees :amount="$factValue" /></span>
                    <span class="group-fact-label" data-i18n="{{ $factKey }}">{{ $factLabel }}</span>
                </span>
            </div>
        @endforeach
    </section>


    {{-- WHERE IT STANDS --}}

    <section class="group-panel glass">

        <div class="group-panel-header">
            <h2 data-i18n="repayment_word">Repayment</h2>
        </div>

        <div class="finance-standing">
            <span>
                <small data-i18n="repayment_word">Repayment</small>
                <strong>{{ $loan->frequencyLabel() }} · <x-rupees :amount="$loan->installment_amount" /></strong>
            </span>
            <span>
                <small data-i18n="rates_word">Rates</small>
                <strong>{{ \App\Models\FinanceLoan::rate($loan->interest_rate) }} <span data-i18n="per_year">a year</span> · {{ $loan->term_days }} <span data-i18n="days_word">days</span></strong>
                <small><span data-i18n="processing_fee">Processing fee</span> {{ \App\Models\FinanceLoan::rate($loan->processing_fee_rate) }} · <span data-i18n="gst_word">GST</span> {{ \App\Models\FinanceLoan::rate($loan->gst_rate) }}</small>
            </span>
            <span>
                <small data-i18n="installments_paid">Instalments paid</small>
                <strong>{{ $standing['installments_paid'] }} / {{ $loan->installments }}</strong>
            </span>
            <span>
                <small data-i18n="first_due_on">First instalment on</small>
                <strong>{{ $loan->first_due_on->format('d M Y') }}</strong>
            </span>
            <span>
                <small data-i18n="last_due_on">Last instalment on</small>
                <strong>{{ $loan->lastDueDate()->format('d M Y') }}</strong>
            </span>
            @if ($standing['overdue'] > 0)
                <span>
                    <small data-i18n="overdue_word">Overdue</small>
                    <strong class="ledger-total-due"><x-rupees :amount="$standing['overdue']" /></strong>
                    <small>{{ $standing['days_overdue'] }} <span data-i18n="days_late">days late</span> · <span data-i18n="since_word">since</span> {{ $standing['overdue_since']->format('d M Y') }}</small>
                </span>
            @endif
            @if ($standing['due_today'] > 0)
                <span>
                    <small data-i18n="due_today">Due today</small>
                    <strong class="finance-due-value"><x-rupees :amount="$standing['due_today']" /></strong>
                </span>
            @elseif ($standing['next_due'] && $standing['state'] !== 'overdue')
                <span>
                    <small data-i18n="next_due">Next due</small>
                    <strong>{{ $standing['next_due']->format('d M Y') }}</strong>
                </span>
            @endif
            @if ($loan->isClosed())
                <span>
                    <small data-i18n="closed_on">Closed on</small>
                    <strong class="ledger-total-paid">{{ $loan->closed_on?->format('d M Y') }}</strong>
                </span>
            @endif
        </div>

        @if ($loan->notes)
            <p class="finance-notes">“{{ $loan->notes }}”</p>
        @endif

        <p class="finance-notes">
            <span data-i18n="given_by">Given by</span> {{ $loan->recorder?->name ?? '—' }}
        </p>

    </section>


    {{-- COLLECT --}}

    @unless ($loan->isClosed())

        <section class="group-panel glass payment-step" id="collect">

            <div class="group-panel-header">
                <h2 data-i18n="record_collection">Record collection</h2>
            </div>

            <form method="POST" action="{{ route('finance.collections.store', $loan) }}" class="payment-form">

                @csrf

                <div class="group-form-grid payment-fields">

                    <div class="group-field">
                        <label class="field-label" for="amount" data-i18n="amount_rupees">Amount (₹)</label>
                        <input type="text" id="amount" name="amount" class="input money-input payment-amount-input" inputmode="numeric" required autocomplete="off"
                            value="{{ old('amount', min($loan->balance(), max($standing['to_collect'], $loan->installment_amount))) }}">
                        <small class="installment-hint">
                            <span data-i18n="balance_word">Balance</span> <x-rupees :amount="$loan->balance()" />
                        </small>
                    </div>

                    <div class="group-field">
                        <label class="field-label" for="collected_at" data-i18n="payment_date_time">Date &amp; time</label>
                        <input type="datetime-local" id="collected_at" name="collected_at" class="input" required
                            value="{{ old('collected_at') ? str_replace(' ', 'T', old('collected_at')) : $now->format('Y-m-d\TH:i') }}"
                            max="{{ $now->format('Y-m-d\TH:i') }}">
                    </div>

                    <div class="group-field">
                        <span class="field-label" data-i18n="payment_method">Payment method</span>
                        <div class="method-options">
                            @foreach (\App\Models\Payment::METHODS as $methodValue => $methodLabel)
                                <label class="method-option">
                                    <input type="radio" name="method" value="{{ $methodValue }}" @checked(old('method', 'cash') === $methodValue)>
                                    <span data-i18n="method_{{ $methodValue }}">{{ $methodLabel }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <div class="group-field">
                        <label class="field-label" for="reference" data-i18n="reference_number">Reference / UPI / cheque no.</label>
                        <input type="text" id="reference" name="reference" class="input" maxlength="100" value="{{ old('reference') }}">
                    </div>

                    <div class="group-field group-field-full">
                        <label class="field-label" for="notes" data-i18n="notes">Notes</label>
                        <input type="text" id="notes" name="notes" class="input" maxlength="1000" value="{{ old('notes') }}">
                    </div>

                </div>

                <div class="form-footer payment-form-footer">
                    <button type="submit" class="save-button">
                        <x-icon name="save" />
                        <span data-i18n="save_payment">Save &amp; get receipt</span>
                    </button>
                </div>

            </form>

        </section>

    @endunless


    {{-- COLLECTIONS --}}

    <section class="group-panel glass">

        <div class="group-panel-header">
            <h2 data-i18n="collections_word">Collections</h2>
            <span class="group-panel-note">{{ $loan->collections->count() }} · <x-rupees :amount="$collectedTotal" /></span>
        </div>

        <div class="ledger-table-wrapper">
            <table class="ledger-table statement-table portal-table">
                <thead>
                    <tr>
                        <th data-i18n="date_time">Date &amp; time</th>
                        <th data-i18n="receipt_no">Receipt No.</th>
                        <th data-i18n="payment_method">Payment method</th>
                        <th data-i18n="recorded_by">Recorded by</th>
                        <th class="ledger-col-total" data-i18n="amount_word">Amount</th>
                        <th class="ledger-col-total" data-i18n="balance_word">Balance</th>
                    </tr>
                </thead>
                <tbody>
                    @php $running = $loan->loan_amount; @endphp
                    @forelse ($loan->collections as $collection)
                        @php $running -= $collection->amount; @endphp
                        <tr>
                            <td class="nowrap" data-label="Date">{{ $collection->collected_at->format('d M Y, h:i A') }}</td>
                            <td class="nowrap" data-label="Receipt">
                                <a href="{{ route('finance.collections.show', $collection) }}" class="statement-receipt-link">{{ $collection->receipt_number }}</a>
                            </td>
                            <td data-label="Method">{{ $collection->methodLabel() }}@if ($collection->reference) · {{ $collection->reference }}@endif</td>
                            <td data-label="Recorded by">{{ $collection->recorder?->name ?? '—' }}</td>
                            <td class="ledger-col-total ledger-total-paid" data-label="Amount"><x-rupees :amount="$collection->amount" /></td>
                            <td @class(['ledger-col-total', 'ledger-total-due' => $running > 0]) data-label="Balance"><strong><x-rupees :amount="max(0, $running)" /></strong></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="statement-no-payments" data-i18n="no_collections_yet">Nothing collected yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

    </section>


    @if ($loan->collections->isEmpty())
        <form
            method="POST"
            action="{{ route('finance.loans.destroy', $loan) }}"
            class="finance-delete"
            onsubmit="return confirm('Delete loan {{ $loan->loan_number }}? Only do this if it was entered by mistake.')"
        >
            @csrf
            @method('DELETE')
            <button type="submit" class="group-action receipt-cancel">
                <x-icon name="trash" />
                <span data-i18n="delete_loan">Delete loan (entered by mistake)</span>
            </button>
        </form>
    @endif

</div>

@endsection
