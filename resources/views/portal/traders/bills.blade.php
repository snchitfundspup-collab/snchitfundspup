@extends('layouts.portal')

@section('title', 'Bills & Statement')

@section('content')

<div class="groups-list-page payments-page portal-page">

    <div class="groups-list-header">
        <div>
            @include('portal.partials.back')
            <h1 class="groups-title" data-i18n="my_bills">Bills &amp; Statement</h1>
            <p class="groups-subtitle" data-i18n="my_bills_subtitle">Every rice bill and payment with SN Traders, and what is left to pay. Tap a bill to download it.</p>
        </div>
        <div class="ledger-actions">
            <a href="{{ route('portal.bills.statement.print') }}" class="group-action" target="_blank" rel="noopener">
                <x-icon name="printer" />
                <span data-i18n="print_statement">Print statement</span>
            </a>
            <a href="{{ route('portal.bills.statement.pdf') }}" class="add-group-button">
                <x-icon name="download" />
                <span data-i18n="download_statement">Download statement</span>
            </a>
        </div>
    </div>


    <section class="payment-summary-bar glass dues-summary">
        <div class="payment-summary-main">
            <span class="payment-summary-range" data-i18n="total_bills">Total bills</span>
            <strong class="payment-summary-total"><x-rupees :amount="$totalSales" /></strong>
        </div>
        <div class="payment-summary-main">
            <span class="payment-summary-range" data-i18n="total_paid">Total paid</span>
            <strong class="payment-summary-total"><x-rupees :amount="$totalReceived" /></strong>
        </div>
        <div class="payment-summary-main">
            @if ($balance < 0)
                <span class="payment-summary-range" data-i18n="advance_word">Advance</span>
                <strong class="payment-summary-total"><x-rupees :amount="-$balance" /></strong>
            @else
                <span class="payment-summary-range" data-i18n="balance_due">Balance due</span>
                <strong @class(['payment-summary-total', 'dues-total-pending' => $balance > 0])><x-rupees :amount="$balance" /></strong>
            @endif
        </div>
    </section>


    <section class="group-panel glass">
        <div class="ledger-table-wrapper">
            <table class="ledger-table statement-table portal-table">
                <thead>
                    <tr>
                        <th data-i18n="date">Date</th>
                        <th data-i18n="entry_word">Entry</th>
                        <th data-i18n="details">Details</th>
                        <th class="ledger-col-total" data-i18n="bill_word">Bill</th>
                        <th class="ledger-col-total" data-i18n="paid">Paid</th>
                        <th class="ledger-col-total" data-i18n="balance">Balance</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($entries as $entry)
                        <tr>
                            <td class="nowrap" data-label="Date">{{ \Illuminate\Support\Carbon::parse($entry['date'])->format('d M Y') }}</td>
                            <td class="nowrap" data-label="Entry">
                                <a
                                    href="{{ $entry['type'] === 'sale' ? route('portal.bills.invoice.pdf', $entry['model']) : route('portal.bills.receipt.pdf', $entry['model']) }}"
                                    class="statement-receipt-link"
                                >
                                    <x-icon name="download" />
                                    {{ $entry['type'] === 'sale' ? 'Invoice' : 'Receipt' }} {{ $entry['number'] }}
                                </a>
                            </td>
                            <td data-label="Details">{{ $entry['details'] }}</td>
                            <td class="ledger-col-total" data-label="Bill">@if ($entry['debit'] > 0)<x-rupees :amount="$entry['debit']" />@endif</td>
                            <td class="ledger-col-total ledger-total-paid" data-label="Paid">@if ($entry['credit'] > 0)<x-rupees :amount="$entry['credit']" />@endif</td>
                            <td @class(['ledger-col-total', 'ledger-total-due' => $entry['balance'] > 0]) data-label="Balance"><strong><x-rupees :amount="$entry['balance']" /></strong></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="statement-no-payments" data-i18n="no_bills_yet">No bills yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

</div>

@endsection
