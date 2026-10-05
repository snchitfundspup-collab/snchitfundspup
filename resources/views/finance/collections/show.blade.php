@extends('layouts.app')

@section('title', 'Receipt '.$collection->receipt_number.' | Sri Lakshmi Micro Finance')

@push('styles')
    @vite([
        'resources/css/groups.css',
        'resources/css/payments.css',
        'resources/css/traders.css',
        'resources/css/finance.css'
    ])
@endpush

@php
    $customer = $collection->customer;
@endphp

@section('content')

<div class="group-show-page payments-page traders-page finance-page">

    <div class="no-print">
        @include('traders.partials.flash')
    </div>


    <div class="receipt-actions no-print">

        <a href="{{ route('finance.collect') }}" class="add-group-button">
            <span data-i18n="collect_next">Collect next</span>
            <x-icon name="arrow-right" />
        </a>

        <button type="button" class="group-action" onclick="window.print()">
            <x-icon name="printer" />
            <span data-i18n="print_receipt">Print</span>
        </button>

        <a href="{{ route('finance.collections.pdf', $collection) }}" class="group-action download-pdf-button">
            <x-icon name="download" />
            <span data-i18n="download_pdf">Download PDF</span>
        </a>

        <a href="{{ route('finance.loans.show', $loan) }}" class="group-action">
            <x-icon name="wallet" />
            <span>{{ $loan->loan_number }}</span>
        </a>

        <form
            method="POST"
            action="{{ route('finance.collections.destroy', $collection) }}"
            onsubmit="return confirm('Cancel receipt {{ $collection->receipt_number }}? The amount goes back onto the loan balance.')"
        >
            @csrf
            @method('DELETE')
            <button type="submit" class="group-action receipt-cancel">
                <x-icon name="x" />
                <span data-i18n="cancel_receipt">Cancel receipt</span>
            </button>
        </form>

    </div>


    <article class="receipt glass">

        <header class="receipt-header">
            <div class="receipt-brand">
                <img class="receipt-logo" src="{{ asset('images/sri-lakshmi-logo.png') }}" alt="Sri Lakshmi Micro Finance">
                <div>
                    <strong class="receipt-company"><span>Sri Lakshmi</span> Micro Finance</strong>
                    <span class="receipt-tagline">Small Loans · Easy Repayment</span>
                </div>
            </div>
            <div class="receipt-title">
                <strong data-i18n="payment_receipt">Payment Receipt</strong>
            </div>
        </header>

        <div class="receipt-meta">
            <div>
                <span data-i18n="receipt_no">Receipt No.</span>
                <strong>{{ $collection->receipt_number }}</strong>
            </div>
            <div class="receipt-meta-right">
                <span data-i18n="date_time">Date &amp; time</span>
                <strong>{{ $collection->collected_at->format('d M Y, h:i A') }}</strong>
            </div>
        </div>

        <table class="receipt-lines">
            <tbody>
                <tr>
                    <th data-i18n="received_from">Received from</th>
                    <td><x-customer-name :customer="$customer" /></td>
                </tr>
                <tr>
                    <th data-i18n="customer_id">Customer ID</th>
                    <td>{{ $customer->customer_code }}</td>
                </tr>
                <tr>
                    <th data-i18n="phone">Phone</th>
                    <td>{{ $customer->phone ?: '—' }}</td>
                </tr>
                <tr>
                    <th data-i18n="for_loan">For loan</th>
                    <td>{{ $loan->loan_number }} · <x-rupees :amount="$loan->principal" /> · {{ $loan->loaned_on->format('d M Y') }}</td>
                </tr>
                <tr>
                    <th data-i18n="payment_method">Payment method</th>
                    <td>{{ $collection->methodLabel() }}@if ($collection->reference) · {{ $collection->reference }}@endif</td>
                </tr>
                @if ($collection->notes)
                    <tr>
                        <th data-i18n="notes">Notes</th>
                        <td>{{ $collection->notes }}</td>
                    </tr>
                @endif
            </tbody>
        </table>

        <div class="receipt-amount">
            <div class="receipt-amount-figure">
                <span data-i18n="amount_received">Amount received</span>
                <strong><x-rupees :amount="$collection->amount" /></strong>
            </div>
            <p class="receipt-amount-words">{{ $collection->amountInWords() }}</p>
        </div>

        <footer class="receipt-footer">
            <div>
                <span data-i18n="loan_balance_now">Loan balance after this</span>
                <strong @class(['is-due' => $balanceAfter > 0])><x-rupees :amount="$balanceAfter" /></strong>
            </div>
            <div>
                <span data-i18n="recorded_by">Recorded by</span>
                <strong>{{ $collection->recorder?->name ?? '—' }}</strong>
            </div>
            <div class="receipt-signature">
                <x-signature :user="$collection->recorder" />
                <span data-i18n="authorised_signature">Authorised signature</span>
            </div>
        </footer>

        <p class="receipt-thanks">Thank you.</p>

    </article>

</div>

@endsection
