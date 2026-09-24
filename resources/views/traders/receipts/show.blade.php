@extends('layouts.app')

@section('title', 'Receipt '.$receipt->receipt_number.' | SN Traders')

@push('styles')
    @vite([
        'resources/css/groups.css',
        'resources/css/payments.css',
        'resources/css/traders.css'
    ])
@endpush

@php
    $customer = $receipt->customer;
@endphp

@section('content')

<div class="group-show-page payments-page traders-page">

    <div class="no-print">
        @include('traders.partials.flash')
    </div>


    <div class="receipt-actions no-print">

        <a href="{{ route('traders.balances.index') }}" class="add-group-button">
            <span data-i18n="customer_balances">Customer Balances</span>
            <x-icon name="arrow-right" />
        </a>

        <button type="button" class="group-action" onclick="window.print()">
            <x-icon name="printer" />
            <span data-i18n="print_receipt">Print</span>
        </button>

        <a href="{{ route('traders.receipts.pdf', $receipt) }}" class="group-action download-pdf-button">
            <x-icon name="download" />
            <span data-i18n="download_pdf">Download PDF</span>
        </a>

        <a href="{{ route('traders.accounts.show', $customer) }}" class="group-action">
            <x-icon name="user-check" />
            <span data-i18n="customer_account">Customer account</span>
        </a>

        <form
            method="POST"
            action="{{ route('traders.receipts.destroy', $receipt) }}"
            onsubmit="return confirm('Cancel receipt {{ $receipt->receipt_number }}? The amount goes back onto the customer\'s credit.')"
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
                <img class="receipt-logo" src="{{ asset('images/sn-chit-funds-logo.png') }}" alt="SN Traders">
                <div>
                    <strong class="receipt-company"><span>SN</span> Traders</strong>
                    <span class="receipt-tagline">Quality Rice · Fair Price</span>
                </div>
            </div>
            <div class="receipt-title">
                <strong data-i18n="payment_receipt">Payment Receipt</strong>
            </div>
        </header>

        <div class="receipt-meta">
            <div>
                <span data-i18n="receipt_no">Receipt No.</span>
                <strong>{{ $receipt->receipt_number }}</strong>
            </div>
            <div class="receipt-meta-right">
                <span data-i18n="date_time">Date &amp; time</span>
                <strong>{{ $receipt->received_at->format('d M Y, h:i A') }}</strong>
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
                @if ($receipt->sale)
                    <tr>
                        <th data-i18n="for_invoice">For invoice</th>
                        <td>{{ $receipt->sale->invoice_number }} · {{ $receipt->sale->sold_on->format('d M Y') }}</td>
                    </tr>
                @endif
                <tr>
                    <th data-i18n="payment_method">Payment method</th>
                    <td>{{ $receipt->methodLabel() }}@if ($receipt->reference) · {{ $receipt->reference }}@endif</td>
                </tr>
                @if ($receipt->notes)
                    <tr>
                        <th data-i18n="notes">Notes</th>
                        <td>{{ $receipt->notes }}</td>
                    </tr>
                @endif
            </tbody>
        </table>

        <div class="receipt-amount">
            <div class="receipt-amount-figure">
                <span data-i18n="amount_received">Amount received</span>
                <strong><x-rupees :amount="$receipt->amount" /></strong>
            </div>
            <p class="receipt-amount-words">{{ $receipt->amountInWords() }}</p>
        </div>

        <footer class="receipt-footer">
            <div>
                <span>{{ $balanceNow < 0 ? 'Advance held' : 'Balance due now' }}</span>
                <strong @class(['is-due' => $balanceNow > 0])><x-rupees :amount="abs($balanceNow)" /></strong>
            </div>
            <div>
                <span data-i18n="recorded_by">Recorded by</span>
                <strong>{{ $receipt->recorder?->name ?? '—' }}</strong>
            </div>
            <div class="receipt-signature">
                <span data-i18n="authorised_signature">Authorised signature</span>
            </div>
        </footer>

        <p class="receipt-thanks">Thank you for your business.</p>

    </article>

</div>

@endsection
