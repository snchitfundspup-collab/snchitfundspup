@extends('layouts.app')

@section('title', 'Profit & Loss | SN Traders')

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

@section('content')

<div class="groups-list-page payments-page traders-page">

    @include('traders.reports.partials.page-top', [
        'titleKey' => 'profit_loss',
        'title' => 'Profit & Loss',
        'subtitleKey' => 'profit_subtitle',
        'subtitle' => 'Sales less the purchase cost of the rice sold, less expenses — the profit shared by the partners.',
    ])


    <div id="paymentsResults">

        <section class="payment-summary-bar glass">
            <div class="payment-summary-main">
                <span class="payment-summary-range">
                    <span data-i18n="net_profit">Net profit</span> · @include('traders.partials.range-label')
                </span>
                <strong @class(['payment-summary-total', 'dues-total-pending' => $summary['net'] < 0])><x-rupees :amount="$summary['net']" /></strong>
                <span class="payment-summary-count">
                    <span data-i18n="gross_profit">Gross profit</span> <x-rupees :amount="$summary['gross']" /> ({{ $summary['margin'] }}%)
                </span>
            </div>
            @include('traders.reports.partials.actions')
        </section>


        @if ($summary['missing_cost'] !== [])
            <p class="traders-note is-warning">
                <span data-i18n="missing_cost_note">No purchase price for</span>
                <strong>{{ implode(', ', $summary['missing_cost']) }}</strong> —
                <a href="{{ route('traders.varieties.index') }}" data-i18n="set_prices">set the prices in Rice Varieties</a>.
            </p>
        @endif


        <div class="report-columns">

            <section class="group-panel glass">
                <div class="group-panel-header">
                    <h2 data-i18n="profit_statement">Profit statement</h2>
                </div>
                <table class="ledger-table statement-table profit-statement">
                    <tbody>
                        <tr>
                            <th data-i18n="sales_word">Sales</th>
                            <td class="ledger-col-total"><x-rupees :amount="$summary['sales']" /></td>
                        </tr>
                        <tr>
                            <th><span data-i18n="less_cost_of_rice">Less: cost of the rice sold</span></th>
                            <td class="ledger-col-total">− <x-rupees :amount="$summary['cost']" /></td>
                        </tr>
                        <tr class="profit-subtotal">
                            <th data-i18n="gross_profit">Gross profit</th>
                            <td @class(['ledger-col-total', 'ledger-total-paid' => $summary['gross'] >= 0, 'ledger-total-due' => $summary['gross'] < 0])><strong><x-rupees :amount="$summary['gross']" /></strong></td>
                        </tr>
                        <tr>
                            <th>
                                <a href="{{ route('traders.expenses.index', ['from' => $filters['from'], 'to' => $filters['to']]) }}" class="dues-member-link">
                                    <span data-i18n="less_expenses">Less: expenses</span>
                                </a>
                                <small class="dues-part-paid">{{ $summary['expense_count'] }} <span data-i18n="entries_word">entries</span></small>
                            </th>
                            <td class="ledger-col-total">− <x-rupees :amount="$summary['expenses']" /></td>
                        </tr>
                        <tr class="profit-total">
                            <th data-i18n="net_profit">Net profit</th>
                            <td @class(['ledger-col-total', 'ledger-total-paid' => $summary['net'] >= 0, 'ledger-total-due' => $summary['net'] < 0])><strong><x-rupees :amount="$summary['net']" /></strong></td>
                        </tr>
                        @foreach ($partnerShares as $partner)
                            <tr>
                                <th><span data-i18n="share_of">Share of</span> {{ $partner['name'] }}</th>
                                <td class="ledger-col-total"><x-rupees :amount="$partner['share']" /></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </section>

            <section class="group-panel glass">
                <div class="group-panel-header">
                    <h2 data-i18n="money_view">For reference</h2>
                </div>
                <table class="ledger-table statement-table profit-statement">
                    <tbody>
                        <tr>
                            <th>
                                <span data-i18n="rice_purchased_period">Rice purchased in this period</span>
                                <small class="dues-part-paid" data-i18n="purchases_note">Not all of it is sold yet, so it is not the cost above.</small>
                            </th>
                            <td class="ledger-col-total"><x-rupees :amount="$summary['purchases']" /></td>
                        </tr>
                        <tr>
                            <th>
                                <span data-i18n="stock_value">Rice in stock now, at cost</span>
                            </th>
                            <td class="ledger-col-total"><x-rupees :amount="$summary['stock_value']" /></td>
                        </tr>
                    </tbody>
                </table>
            </section>

        </div>


        @if ($rows->isNotEmpty())
            <section class="group-panel glass payment-step">
                <div class="group-panel-header">
                    <h2 data-i18n="profit_by_rice">Profit by rice</h2>
                </div>
                <div class="ledger-table-wrapper">
                    <table class="ledger-table statement-table">
                        <thead>
                            <tr>
                                <th data-i18n="rice_variety">Rice variety</th>
                                <th class="ledger-col-total" data-i18n="bags_sold">Bags sold</th>
                                <th class="ledger-col-total" data-i18n="sales_word">Sales</th>
                                <th class="ledger-col-total" data-i18n="selling_price">Selling price</th>
                                <th class="ledger-col-total" data-i18n="purchase_price">Purchase price</th>
                                <th class="ledger-col-total" data-i18n="cost_word">Cost</th>
                                <th class="ledger-col-total" data-i18n="profit_per_bag">Profit / bag</th>
                                <th class="ledger-col-total" data-i18n="profit_word">Profit</th>
                                <th class="ledger-col-total" data-i18n="margin_word">Margin</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($rows as $row)
                                <tr>
                                    <td><strong>{{ $row['name'] }}</strong></td>
                                    <td class="ledger-col-total">{{ $row['bags'] }}</td>
                                    <td class="ledger-col-total"><x-rupees :amount="$row['sales']" /></td>
                                    <td class="ledger-col-total"><x-rupees :amount="$row['sale_rate']" /></td>
                                    <td class="ledger-col-total">@if ($row['cost_rate'] !== null)<x-rupees :amount="$row['cost_rate']" />@else — @endif</td>
                                    <td class="ledger-col-total"><x-rupees :amount="$row['cost']" /></td>
                                    <td class="ledger-col-total">@if ($row['cost_rate'] !== null)<x-rupees :amount="$row['profit_per_bag']" />@else — @endif</td>
                                    <td @class(['ledger-col-total', 'ledger-total-paid' => $row['profit'] >= 0, 'ledger-total-due' => $row['profit'] < 0])><strong><x-rupees :amount="$row['profit']" /></strong></td>
                                    <td class="ledger-col-total">{{ $row['margin'] }}%</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <th data-i18n="total">Total</th>
                                <td class="ledger-col-total">{{ $rows->sum('bags') }}</td>
                                <td class="ledger-col-total"><x-rupees :amount="$summary['sales']" /></td>
                                <td colspan="2"></td>
                                <td class="ledger-col-total"><x-rupees :amount="$summary['cost']" /></td>
                                <td></td>
                                <td class="ledger-col-total"><strong><x-rupees :amount="$summary['gross']" /></strong></td>
                                <td class="ledger-col-total">{{ $summary['margin'] }}%</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </section>
        @else
            <div class="groups-empty glass">
                <span class="groups-empty-icon icon-3d icon-3d-green"><x-icon name="rupee" /></span>
                <strong data-i18n="no_sales">No sales in this period</strong>
            </div>
        @endif

    </div>

</div>

@endsection
