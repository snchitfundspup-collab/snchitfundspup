@extends('layouts.app')

@section('title', 'Day Book | SN Traders')

@push('styles')
    @vite([
        'resources/css/groups.css',
        'resources/css/payments.css',
        'resources/css/traders.css'
    ])
@endpush

@push('scripts')
    @vite('resources/js/payments.js')
@endpush

@php
    $methodKeys = ['cash' => 'method_cash', 'upi' => 'method_upi', 'bank' => 'method_bank', 'cheque' => 'method_cheque'];
@endphp

@section('content')

<div class="groups-list-page payments-page traders-page">

    @include('traders.reports.partials.page-top', [
        'titleKey' => 'day_book',
        'title' => 'Day Book',
        'subtitleKey' => 'day_book_subtitle',
        'subtitle' => 'Each day: sales, money received, rice purchased and expenses, and the money left at the end of the day.',
    ])


    <div id="paymentsResults">

        <section class="payment-summary-bar glass">
            <div class="payment-summary-main">
                <span class="payment-summary-range">
                    <span data-i18n="net_cash">Money in − money out</span> · @include('traders.partials.range-label')
                </span>
                <strong @class(['payment-summary-total', 'dues-total-pending' => $summary['net'] < 0])><x-rupees :amount="$summary['net']" /></strong>
                <span class="payment-summary-count">
                    <span data-i18n="sales_word">Sales</span> <x-rupees :amount="$summary['sales']" />
                    · <span data-i18n="received_word">received</span> <x-rupees :amount="$summary['received']" />
                </span>
            </div>
            <div class="payment-summary-methods">
                @foreach ($methods as $method => $methodLabel)
                    @if ($summary['by_method'][$method] > 0)
                        <span class="payment-method-total">
                            <span data-i18n="{{ $methodKeys[$method] ?? '' }}">{{ $methodLabel }}</span>
                            <strong><x-rupees :amount="$summary['by_method'][$method]" /></strong>
                        </span>
                    @endif
                @endforeach
                <span class="payment-method-total">
                    <span data-i18n="purchases_word_title">Purchases</span>
                    <strong><x-rupees :amount="$summary['purchases']" /></strong>
                </span>
                <span class="payment-method-total">
                    <span data-i18n="menu_expenses">Expenses</span>
                    <strong><x-rupees :amount="$summary['expenses']" /></strong>
                </span>
            </div>
            @include('traders.reports.partials.actions')
        </section>


        @if ($rows->isEmpty())

            <div class="groups-empty glass">
                <span class="groups-empty-icon icon-3d icon-3d-blue"><x-icon name="calendar" /></span>
                <strong data-i18n="nothing_in_period">Nothing recorded in this period</strong>
            </div>

        @else

            <section class="group-panel glass">
                <div class="ledger-table-wrapper">
                    <table class="ledger-table statement-table">
                        <thead>
                            <tr>
                                <th data-i18n="date">Date</th>
                                <th class="ledger-col-total" data-i18n="sales_word">Sales</th>
                                @foreach ($methods as $method => $methodLabel)
                                    <th class="ledger-col-total" data-i18n="{{ $methodKeys[$method] ?? '' }}">{{ $methodLabel }}</th>
                                @endforeach
                                <th class="ledger-col-total" data-i18n="money_in">Money in</th>
                                <th class="ledger-col-total" data-i18n="purchases_word_title">Purchases</th>
                                <th class="ledger-col-total" data-i18n="menu_expenses">Expenses</th>
                                <th class="ledger-col-total" data-i18n="net_word">Net</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($rows as $row)
                                <tr>
                                    <td class="nowrap">
                                        <strong>{{ \Illuminate\Support\Carbon::parse($row['date'])->format('d M Y') }}</strong>
                                        <small class="dues-part-paid">{{ \Illuminate\Support\Carbon::parse($row['date'])->format('l') }}</small>
                                    </td>
                                    <td class="ledger-col-total">
                                        @if ($row['sales'] > 0)
                                            <a href="{{ route('traders.sales.index', ['from' => $row['date'], 'to' => $row['date']]) }}" class="dues-member-link"><x-rupees :amount="$row['sales']" /></a>
                                            <small class="dues-part-paid">{{ $row['invoices'] }} <span data-i18n="{{ $row['invoices'] === 1 ? 'invoice_word' : 'invoices_word' }}">{{ $row['invoices'] === 1 ? 'invoice' : 'invoices' }}</span></small>
                                        @else
                                            —
                                        @endif
                                    </td>
                                    @foreach (array_keys($methods) as $method)
                                        <td class="ledger-col-total">@if ($row['by_method'][$method] > 0)<x-rupees :amount="$row['by_method'][$method]" />@else — @endif</td>
                                    @endforeach
                                    <td class="ledger-col-total ledger-total-paid"><x-rupees :amount="$row['received']" /></td>
                                    <td class="ledger-col-total">@if ($row['purchases'] > 0)<x-rupees :amount="$row['purchases']" />@else — @endif</td>
                                    <td class="ledger-col-total">@if ($row['expenses'] > 0)<x-rupees :amount="$row['expenses']" />@else — @endif</td>
                                    <td @class(['ledger-col-total', 'ledger-total-due' => $row['net'] < 0])><strong><x-rupees :amount="$row['net']" /></strong></td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <th><span data-i18n="total">Total</span> ({{ $rows->count() }} <span data-i18n="days_word">days</span>)</th>
                                <td class="ledger-col-total"><x-rupees :amount="$summary['sales']" /></td>
                                @foreach (array_keys($methods) as $method)
                                    <td class="ledger-col-total"><x-rupees :amount="$summary['by_method'][$method]" /></td>
                                @endforeach
                                <td class="ledger-col-total"><x-rupees :amount="$summary['received']" /></td>
                                <td class="ledger-col-total"><x-rupees :amount="$summary['purchases']" /></td>
                                <td class="ledger-col-total"><x-rupees :amount="$summary['expenses']" /></td>
                                <td class="ledger-col-total"><strong><x-rupees :amount="$summary['net']" /></strong></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </section>

            <p class="traders-note" data-i18n="day_book_note">Money in is what customers paid that day (at a sale or later). Money out is rice purchased plus expenses.</p>

        @endif

    </div>

</div>

@endsection
