@extends('layouts.app')

@section('title', 'New Loan | Sri Lakshmi Micro Finance')

@push('styles')
    @vite([
        'resources/css/create.css',
        'resources/css/groups.css',
        'resources/css/payments.css',
        'resources/css/traders.css',
        'resources/css/finance.css'
    ])
@endpush

@push('scripts')
    @vite(['resources/js/traders.js', 'resources/js/finance.js'])
@endpush

@section('content')

<div class="group-show-page payments-page traders-page finance-page">

    @include('traders.partials.flash')


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
                <h1 class="group-hero-title" data-i18n="new_loan">New Loan</h1>
                <p class="group-hero-meta" data-i18n="new_loan_subtitle">The processing fee and GST are cut when the money is given; interest per year is added for the loan period and the total is repaid in instalments.</p>
            </div>
        </div>

    </section>


    <a href="{{ route('finance.capital.index') }}" class="action-alert glass finance-available-alert" id="loanAvailable" data-available="{{ $available }}">
        <span class="stat-icon icon-3d icon-3d-green"><x-icon name="wallet" /></span>
        <span>
            <span data-i18n="available_to_lend">Available to lend</span>
            <strong @class(['ledger-total-due' => $available < 0])><x-rupees :amount="$available" /></strong>
            <small class="finance-available-warning" id="loanAvailableWarning" hidden data-i18n="not_enough_cash">This loan needs more than the money available.</small>
        </span>
        <x-icon name="arrow-right" />
    </a>

    <section class="group-panel glass payment-step">

        <form method="POST" action="{{ route('finance.loans.store') }}" class="payment-form" id="loanForm">

            @csrf

            <div class="group-form-grid payment-fields">

                <div class="group-field group-field-full">
                    <label class="field-label" for="customer_id" data-i18n="customer_word">Customer</label>
                    <input type="search" class="input picker-filter" placeholder="Type to find the customer" data-filter-select="customer_id" autocomplete="off">
                    <select id="customer_id" name="customer_id" class="input" required>
                        <option value="">Choose customer…</option>
                        @foreach ($customers as $customerOption)
                            <option value="{{ $customerOption->id }}" @selected((int) old('customer_id', $selectedCustomerId) === $customerOption->id)>
                                {{ $customerOption->name }}{{ $customerOption->remarks ? ' ('.$customerOption->remarks.')' : '' }} · {{ $customerOption->customer_code }} · {{ $customerOption->phone }}
                            </option>
                        @endforeach
                    </select>
                    <small class="installment-hint">
                        <a href="{{ route('customers.create') }}" data-i18n="add_new_customer">New customer? Add them first</a>
                    </small>
                </div>

                <div class="group-field">
                    <label class="field-label" for="loaned_on" data-i18n="loan_date">Loan date</label>
                    <input type="date" id="loaned_on" name="loaned_on" class="input" required
                        value="{{ old('loaned_on', $today->toDateString()) }}" max="{{ $today->toDateString() }}">
                </div>

                <div class="group-field">
                    <label class="field-label" for="principal" data-i18n="loan_amount_rupees">Loan amount (₹)</label>
                    <input type="text" id="principal" name="principal" class="input money-input" inputmode="numeric" required autocomplete="off"
                        value="{{ old('principal') }}" placeholder="e.g. 10000">
                </div>

                <div class="group-field finance-rate-pair">
                    <label>
                        <span class="field-label" data-i18n="processing_fee_rate">Processing fee (%)</span>
                        <input type="text" id="processing_fee_rate" name="processing_fee_rate" class="input" inputmode="decimal" required autocomplete="off"
                            value="{{ old('processing_fee_rate', $defaults['processing_fee_rate']) }}">
                    </label>
                    <label>
                        <span class="field-label" data-i18n="gst_rate">GST on the fee (%)</span>
                        <input type="text" id="gst_rate" name="gst_rate" class="input" inputmode="decimal" required autocomplete="off"
                            value="{{ old('gst_rate', $defaults['gst_rate']) }}">
                    </label>
                </div>

                <div class="group-field group-field-full">
                    <div class="finance-breakdown" aria-live="polite">
                        <span><small data-i18n="processing_fee">Processing fee</small><strong id="loanFee">₹0</strong></span>
                        <span><small data-i18n="gst_word">GST</small><strong id="loanGst">₹0</strong></span>
                        <span><small data-i18n="customer_receives">Customer receives in hand</small><strong class="finance-in-hand" id="loanInHand">₹0</strong></span>
                    </div>
                </div>

                <div class="group-field">
                    <label class="field-label" for="interest_rate" data-i18n="interest_rate">Interest (% per year)</label>
                    <input type="text" id="interest_rate" name="interest_rate" class="input" inputmode="decimal" required autocomplete="off"
                        value="{{ old('interest_rate', $defaults['interest_rate']) }}">
                </div>

                <div class="group-field">
                    <span class="field-label" data-i18n="repayment_word">Repayment</span>
                    <div class="method-options">
                        @foreach (\App\Models\FinanceLoan::FREQUENCIES as $frequencyValue => $frequencyLabel)
                            <label class="method-option">
                                <input type="radio" name="frequency" value="{{ $frequencyValue }}" data-installments="{{ $defaults['installments'][$frequencyValue] }}" @checked(old('frequency', 'daily') === $frequencyValue)>
                                <span data-i18n="frequency_{{ $frequencyValue }}">{{ $frequencyLabel }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <div class="group-field">
                    <label class="field-label" for="installments">
                        <span data-i18n="number_of_days" id="installmentsDaysLabel">Number of days</span>
                        <span data-i18n="number_of_weeks" id="installmentsWeeksLabel" hidden>Number of weeks</span>
                    </label>
                    <input type="text" id="installments" name="installments" class="input" inputmode="numeric" required autocomplete="off"
                        value="{{ old('installments', $defaults['installments'][old('frequency', 'daily')] ?? 100) }}">
                </div>

                <div class="group-field">
                    <label class="field-label" for="first_due_on" data-i18n="first_due_on">First instalment on</label>
                    <input type="date" id="first_due_on" name="first_due_on" class="input" required
                        value="{{ old('first_due_on', $defaultFirstDue[old('frequency', 'daily')] ?? $defaultFirstDue['daily']) }}">
                </div>

                <div class="group-field group-field-full">
                    <div class="finance-breakdown" aria-live="polite">
                        <span><small data-i18n="interest_word">Interest</small><strong id="loanInterest">₹0</strong><small id="loanInterestNote"></small></span>
                        <span><small data-i18n="total_to_repay">Total to repay</small><strong id="loanTotal">₹0</strong></span>
                    </div>
                </div>

                <div class="group-field">
                    <label class="field-label" for="installment_amount" data-i18n="installment_amount">Instalment amount (₹)</label>
                    <input type="text" id="installment_amount" name="installment_amount" class="input money-input" inputmode="numeric" autocomplete="off"
                        value="{{ old('installment_amount') }}" data-manual="{{ old('installment_amount') ? '1' : '' }}">
                    <small class="installment-hint">
                        <span data-i18n="installment_auto_note">Worked out for you — type to change it.</span>
                        <button type="button" class="finance-auto-button" id="installmentAuto" hidden data-i18n="use_worked_out">Use the worked-out amount</button>
                    </small>
                </div>

                <div class="group-field group-field-full">
                    <p class="finance-plan" id="loanPlan" aria-live="polite" hidden></p>
                </div>

                <div class="group-field group-field-full">
                    <label class="field-label" for="notes" data-i18n="notes">Notes</label>
                    <input type="text" id="notes" name="notes" class="input" maxlength="1000" value="{{ old('notes') }}">
                </div>

            </div>

            <div class="form-footer payment-form-footer">
                <button type="submit" class="save-button">
                    <x-icon name="save" />
                    <span data-i18n="save_loan">Save loan</span>
                </button>
            </div>

        </form>

    </section>

</div>

@endsection
