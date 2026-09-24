@extends('layouts.app')

@section('title', 'Rice Sales | SN Traders')

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
    $speeds = [
        'fast' => ['due-badge-ok', 'speed_fast', 'Fast'],
        'steady' => ['due-badge-done', 'speed_steady', 'Steady'],
        'slow' => ['due-badge-month', 'speed_slow', 'Slow'],
        'none' => ['due-badge-upcoming', 'speed_none', 'Not sold'],
    ];
    $largest = max(1, $rows->max('bags') ?? 1);
@endphp

@section('content')

<div class="groups-list-page payments-page traders-page">

    @include('traders.reports.partials.page-top', [
        'titleKey' => 'rice_sales_report',
        'title' => 'Rice Sales',
        'subtitleKey' => 'rice_sales_subtitle',
        'subtitle' => 'Which rice sells fastest — bags sold, share of sales, and how long the stock lasts at this pace.',
    ])


    <div id="paymentsResults">

        <section class="payment-summary-bar glass">
            <div class="payment-summary-main">
                <span class="payment-summary-range">@include('traders.partials.range-label')</span>
                <strong class="payment-summary-total">{{ $summary['bags'] }} <span data-i18n="bags_lower">bags</span></strong>
                <span class="payment-summary-count">
                    <x-rupees :amount="$summary['amount']" />
                    · {{ $summary['invoices'] }} <span data-i18n="{{ $summary['invoices'] === 1 ? 'invoice_word' : 'invoices_word' }}">{{ $summary['invoices'] === 1 ? 'invoice' : 'invoices' }}</span>
                    · {{ rtrim(rtrim(number_format($summary['per_day'], 1), '0'), '.') }} <span data-i18n="bags_a_day">bags a day</span>
                </span>
            </div>
            @include('traders.reports.partials.actions')
        </section>


        @if ($rows->isEmpty())

            <div class="groups-empty glass">
                <span class="groups-empty-icon icon-3d icon-3d-green"><x-icon name="chart" /></span>
                <strong data-i18n="no_varieties">No rice varieties yet</strong>
            </div>

        @else

            <section class="group-panel glass">
                <div class="group-panel-header">
                    <h2 data-i18n="fastest_selling">Fastest selling rice</h2>
                </div>
                <div class="ledger-table-wrapper">
                    <table class="ledger-table statement-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th data-i18n="rice_variety">Rice variety</th>
                                <th class="ledger-col-total" data-i18n="bags_sold">Bags sold</th>
                                <th class="ledger-col-total" data-i18n="sales_word">Sales</th>
                                <th class="ledger-col-total" data-i18n="avg_rate_bag">Avg rate / bag</th>
                                <th class="ledger-col-total" data-i18n="bags_per_day">Bags / day</th>
                                <th class="ledger-col-total" data-i18n="in_stock_bags">In stock (bags)</th>
                                <th class="ledger-col-total" data-i18n="stock_lasts">Stock lasts</th>
                                <th data-i18n="speed_word">Speed</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($rows as $row)
                                @php [$speedClass, $speedKey, $speedLabel] = $speeds[$row['speed']]; @endphp
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>
                                        <strong>{{ $row['name'] }}</strong>
                                        <small class="dues-part-paid">
                                            {{ $row['share'] }}% · {{ $row['customers'] }} <span data-i18n="{{ $row['customers'] === 1 ? 'customer_lower' : 'customers_word' }}">{{ $row['customers'] === 1 ? 'customer' : 'customers' }}</span>
                                        </small>
                                        <div class="stock-bar" aria-hidden="true">
                                            <span style="width: {{ $row['bags'] / $largest * 100 }}%"></span>
                                        </div>
                                    </td>
                                    <td class="ledger-col-total"><strong>{{ $row['bags'] }}</strong></td>
                                    <td class="ledger-col-total"><x-rupees :amount="$row['amount']" /></td>
                                    <td class="ledger-col-total">@if ($row['bags'] > 0)<x-rupees :amount="$row['average_rate']" />@else — @endif</td>
                                    <td class="ledger-col-total">{{ rtrim(rtrim(number_format($row['per_day'], 1), '0'), '.') }}</td>
                                    <td @class(['ledger-col-total', 'stock-negative' => $row['stock_bags'] < 0])>{{ $row['stock_bags'] }}</td>
                                    <td class="ledger-col-total">
                                        @if ($row['days_left'] !== null)
                                            <span @class(['ledger-total-due' => $row['days_left'] <= 7])>{{ $row['days_left'] }} <span data-i18n="days_word">days</span></span>
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td><span class="due-badge {{ $speedClass }}" data-i18n="{{ $speedKey }}">{{ $speedLabel }}</span></td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="2" data-i18n="total">Total</th>
                                <td class="ledger-col-total"><strong>{{ $summary['bags'] }}</strong></td>
                                <td class="ledger-col-total"><x-rupees :amount="$summary['amount']" /></td>
                                <td></td>
                                <td class="ledger-col-total">{{ rtrim(rtrim(number_format($summary['per_day'], 1), '0'), '.') }}</td>
                                <td class="ledger-col-total">{{ $rows->sum('stock_bags') }}</td>
                                <td colspan="2"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </section>


            @if ($topCustomers->isNotEmpty())
                <section class="group-panel glass payment-step">
                    <div class="group-panel-header">
                        <h2 data-i18n="top_customers">Top customers</h2>
                    </div>
                    <div class="ledger-table-wrapper">
                        <table class="ledger-table statement-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th data-i18n="customer_word">Customer</th>
                                    <th class="ledger-col-total" data-i18n="invoices_title">Invoices</th>
                                    <th class="ledger-col-total" data-i18n="bags_word">Bags</th>
                                    <th class="ledger-col-total" data-i18n="sales_word">Sales</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($topCustomers as $top)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>
                                            <a href="{{ route('traders.accounts.show', $top['customer']) }}" class="dues-member-link">
                                                <strong><x-customer-name :customer="$top['customer']" /></strong>
                                            </a>
                                            <span class="dues-member-code">{{ $top['customer']->customer_code }}</span>
                                        </td>
                                        <td class="ledger-col-total">{{ $top['invoices'] }}</td>
                                        <td class="ledger-col-total">{{ $top['bags'] }}</td>
                                        <td class="ledger-col-total"><strong><x-rupees :amount="$top['amount']" /></strong></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </section>
            @endif

        @endif

    </div>

</div>

@endsection
