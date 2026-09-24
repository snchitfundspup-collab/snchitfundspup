@extends('layouts.app')

@section('title', 'Account – '.$customer->name.' | SN Traders')

@push('styles')
    @vite([
        'resources/css/groups.css',
        'resources/css/payments.css',
        'resources/css/traders.css'
    ])
@endpush

@section('content')

<div class="groups-list-page payments-page traders-page">

    @include('traders.partials.flash')


    <div class="groups-list-header">
        <div>
            <a href="{{ route('traders.balances.index') }}" class="group-back-link">
                <x-icon name="arrow-left" />
                <span data-i18n="customer_balances">Customer Balances</span>
            </a>
            <h1 class="groups-title"><x-customer-name :customer="$customer" /></h1>
            <p class="groups-subtitle">
                {{ $customer->customer_code }} · {{ $customer->phone ?: '—' }}
                @if ($customer->phone)
                    <a href="tel:{{ preg_replace('/[^\d+]/', '', $customer->phone) }}" class="member-call-button">
                        <x-icon name="phone" />
                        <span data-i18n="call">Call</span>
                    </a>
                @endif
            </p>
        </div>
        <div class="ledger-actions">
            <button type="button" class="group-action" onclick="window.print()">
                <x-icon name="printer" />
                <span data-i18n="print_statement">Print statement</span>
            </button>
            <a href="{{ route('traders.accounts.pdf', $customer) }}" class="add-group-button">
                <x-icon name="download" />
                <span data-i18n="download_pdf">Download PDF</span>
            </a>
        </div>
    </div>


    <section class="payment-summary-bar glass dues-summary">
        <div class="payment-summary-main">
            <span class="payment-summary-range" data-i18n="total_sales">Total sales</span>
            <strong class="payment-summary-total"><x-rupees :amount="$totalSales" /></strong>
        </div>
        <div class="payment-summary-main">
            <span class="payment-summary-range" data-i18n="total_received">Total received</span>
            <strong class="payment-summary-total"><x-rupees :amount="$totalReceived" /></strong>
        </div>
        <div class="payment-summary-main">
            @if ($balance < 0)
                <span class="payment-summary-range" data-i18n="advance_word">Advance</span>
                <strong class="payment-summary-total"><x-rupees :amount="-$balance" /></strong>
            @else
                <span class="payment-summary-range" data-i18n="balance_due">Balance due</span>
                <strong class="payment-summary-total dues-total-pending"><x-rupees :amount="$balance" /></strong>
            @endif
        </div>
        <div class="payment-summary-actions no-print">
            <a href="{{ route('traders.sales.create', ['customer' => $customer->id]) }}" class="group-action">
                <x-icon name="plus" />
                <span data-i18n="new_sale">New Sale</span>
            </a>
            <a href="{{ route('traders.receipts.create', ['customer' => $customer->id]) }}" class="add-group-button">
                <x-icon name="rupee" />
                <span data-i18n="receive_payment">Receive Payment</span>
            </a>
        </div>
    </section>


    <section class="ledger-sheet statement-sheet glass">

        <header class="ledger-print-head">
            <img class="receipt-logo" src="{{ asset('images/sn-chit-funds-logo.png') }}" alt="SN Traders">
            <div>
                <strong class="receipt-company"><span>SN</span> Traders</strong>
                <span class="receipt-tagline">Quality Rice · Fair Price</span>
            </div>
            <div class="ledger-print-title">
                <strong>Customer Account</strong>
                <span>{{ now(config('app.business_timezone'))->format('d M Y, h:i A') }}</span>
            </div>
        </header>

        <div class="ledger-table-wrapper">
            <table class="ledger-table statement-table">
                <thead>
                    <tr>
                        <th data-i18n="expense_date">Date</th>
                        <th data-i18n="entry_word">Entry</th>
                        <th data-i18n="details">Details</th>
                        <th class="ledger-col-total" data-i18n="sale_word">Sale</th>
                        <th class="ledger-col-total" data-i18n="received_word">Received</th>
                        <th class="ledger-col-total" data-i18n="balance">Balance</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($entries as $entry)
                        <tr>
                            <td>{{ \Illuminate\Support\Carbon::parse($entry['date'])->format('d M Y') }}</td>
                            <td>
                                <a href="{{ $entry['type'] === 'sale' ? route('traders.sales.show', $entry['model']) : route('traders.receipts.show', $entry['model']) }}" class="statement-receipt-link">
                                    {{ $entry['type'] === 'sale' ? 'Invoice' : 'Receipt' }} {{ $entry['number'] }}
                                </a>
                            </td>
                            <td>{{ $entry['details'] }}</td>
                            <td class="ledger-col-total">@if ($entry['debit'] > 0)<x-rupees :amount="$entry['debit']" />@endif</td>
                            <td class="ledger-col-total ledger-total-paid">@if ($entry['credit'] > 0)<x-rupees :amount="$entry['credit']" />@endif</td>
                            <td @class(['ledger-col-total', 'ledger-total-due' => $entry['balance'] > 0])><strong><x-rupees :amount="$entry['balance']" /></strong></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="statement-no-payments" data-i18n="no_trades_yet">No sales or receipts yet.</td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="3" data-i18n="total">Total</th>
                        <td class="ledger-col-total"><strong><x-rupees :amount="$totalSales" /></strong></td>
                        <td class="ledger-col-total"><strong><x-rupees :amount="$totalReceived" /></strong></td>
                        <td class="ledger-col-total"><strong><x-rupees :amount="$balance" /></strong></td>
                    </tr>
                </tfoot>
            </table>
        </div>

    </section>

</div>

@endsection
