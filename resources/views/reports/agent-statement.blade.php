{{-- Agent Statement (Chit Funds or Micro Finance, from the route): one
     staff member's customers brought in, receipts collected day by day and
     month-by-month totals for a period. --}}
@extends('layouts.app')

@section('title', 'Agent Statement | '.($isFinance ? 'Sri Lakshmi Micro Finance' : 'Sri Lakshmi Chit Funds'))

@push('styles')
    @vite([
        'resources/css/groups.css',
        'resources/css/payments.css',
        'resources/css/traders.css',
        'resources/css/finance.css'
    ])
@endpush

@push('scripts')
    @vite('resources/js/payments.js')
@endpush

@php
    $agentFilter = view('reports.partials.agent-filter', ['staff' => $staff, 'agent' => $agent])->render();
@endphp

@section('content')

<div class="groups-list-page payments-page traders-page finance-page">

    <div class="groups-list-header">
        <div>
            <h1 class="groups-title" data-i18n="agent_statement">Agent Statement</h1>
            <p class="groups-subtitle" data-i18n="agent_statement_subtitle">The customers a staff member brought in and the money they collected — day by day and month by month.</p>
        </div>
    </div>

    @include('traders.partials.list-filters', [
        'route' => $routePrefix,
        'routeParams' => [],
        'keep' => ['agent' => $agent->id],
        'searchPlaceholder' => null,
        'extra' => $agentFilter,
    ])

    <div id="paymentsResults">

        <section class="payment-summary-bar glass">
            <div class="payment-summary-main">
                <span class="payment-summary-range"><span data-i18n="collected_word">Collected</span> · {{ $agent->name }} · @include('traders.partials.range-label')</span>
                <strong class="payment-summary-total"><x-rupees :amount="$summary['collected']" /></strong>
                <span class="payment-summary-count">{{ $summary['receipts'] }} <span data-i18n="receipts_word">receipts</span></span>
            </div>
            <div class="payment-summary-main">
                <span class="payment-summary-range" data-i18n="customers_brought_in">Customers brought in</span>
                <strong class="payment-summary-total">{{ $summary['customers'] }}</strong>
                <span class="payment-summary-count">
                    {{ $summary['joined'] }} <span data-i18n="{{ $isFinance ? 'loans_word' : 'seats_word' }}">{{ $isFinance ? 'loans' : 'seat(s)' }}</span>
                    · <x-rupees :amount="$summary['joined_amount']" />
                </span>
            </div>
            <div class="payment-summary-actions">
                <a href="{{ route($routePrefix.'.print', $query) }}" class="group-action" target="_blank" rel="noopener">
                    <x-icon name="printer" />
                    <span data-i18n="print_report">Print report</span>
                </a>
                <a href="{{ route($routePrefix.'.pdf', $query) }}" class="add-group-button">
                    <x-icon name="download" />
                    <span data-i18n="download_pdf">Download PDF</span>
                </a>
            </div>
        </section>

        @if ($joined->isEmpty() && $collections->isEmpty())
            <div class="groups-empty glass">
                <span class="groups-empty-icon icon-3d icon-3d-blue"><x-icon name="users" /></span>
                <strong data-i18n="no_agent_entries">Nothing for this agent in this period</strong>
            </div>
        @else

            {{-- Customers brought in --}}
            <section class="group-panel glass payment-step">
                <div class="group-panel-header">
                    <h2 data-i18n="customers_brought_in">Customers brought in</h2>
                </div>
                @if ($joined->isEmpty())
                    <p class="members-empty" data-i18n="no_customers_brought_in">No new customers in this period.</p>
                @else
                    <div class="ledger-table-wrapper">
                        <table class="ledger-table statement-table portal-table">
                            <thead>
                                <tr>
                                    <th data-i18n="date">Date</th>
                                    <th data-i18n="customer_word">Customer</th>
                                    <th data-i18n="{{ $isFinance ? 'loan_no' : 'group_seat' }}">{{ $isFinance ? 'Loan No.' : 'Group · Seat' }}</th>
                                    <th class="ledger-col-total" data-i18n="{{ $isFinance ? 'loan_amount_word' : 'chit_value' }}">{{ $isFinance ? 'Loan amount' : 'Chit value' }}</th>
                                    <th class="ledger-col-total" data-i18n="collected_word">Collected</th>
                                    <th class="ledger-col-total" data-i18n="{{ $isFinance ? 'balance_word' : 'due_now' }}">{{ $isFinance ? 'Balance' : 'Due now' }}</th>
                                    <th data-i18n="status">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($joined as $row)
                                    <tr>
                                        <td class="nowrap" data-label="Date">{{ $row['date']->format('d M Y') }}</td>
                                        <td data-label="Customer"><x-customer-name :customer="$row['customer']" /> <small>{{ $row['customer']->customer_code }}</small></td>
                                        <td data-label="{{ $isFinance ? 'Loan No.' : 'Group · Seat' }}"><a href="{{ $row['link'] }}">{{ $row['reference'] }}</a></td>
                                        <td class="ledger-col-total" data-label="{{ $isFinance ? 'Loan amount' : 'Chit value' }}"><x-rupees :amount="$row['amount']" /></td>
                                        <td class="ledger-col-total ledger-total-paid" data-label="Collected"><x-rupees :amount="$row['collected']" /></td>
                                        <td @class(['ledger-col-total', 'ledger-total-due' => $row['left'] > 0]) data-label="{{ $isFinance ? 'Balance' : 'Due now' }}"><x-rupees :amount="$row['left']" /></td>
                                        <td data-label="Status">@include('reports.partials.agent-state-badge', ['state' => $row['state']])</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th colspan="3" data-i18n="total">Total</th>
                                    <td class="ledger-col-total"><x-rupees :amount="$summary['joined_amount']" /></td>
                                    <td class="ledger-col-total"><x-rupees :amount="$summary['joined_collected']" /></td>
                                    <td class="ledger-col-total"><x-rupees :amount="$summary['left']" /></td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                @endif
            </section>

            {{-- Month by month --}}
            <section class="group-panel glass payment-step">
                <div class="group-panel-header">
                    <h2 data-i18n="month_by_month">Month-by-month</h2>
                </div>
                <div class="ledger-table-wrapper">
                    <table class="ledger-table statement-table portal-table">
                        <thead>
                            <tr>
                                <th data-i18n="month_heading">Month</th>
                                <th class="ledger-col-total" data-i18n="joined_word">Joined</th>
                                <th class="ledger-col-total" data-i18n="{{ $isFinance ? 'loan_amount_word' : 'chit_value' }}">{{ $isFinance ? 'Loan amount' : 'Chit value' }}</th>
                                <th class="ledger-col-total" data-i18n="receipt_count">Receipts</th>
                                <th class="ledger-col-total" data-i18n="collected_word">Collected</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($months as $month)
                                <tr>
                                    <td class="nowrap" data-label="Month">{{ $month['month']->format('F Y') }}</td>
                                    <td class="ledger-col-total" data-label="Joined">{{ $month['joined'] ?: '—' }}</td>
                                    <td class="ledger-col-total" data-label="{{ $isFinance ? 'Loan amount' : 'Chit value' }}">@if ($month['joined_amount'] > 0)<x-rupees :amount="$month['joined_amount']" />@else — @endif</td>
                                    <td class="ledger-col-total" data-label="Receipts">{{ $month['receipts'] ?: '—' }}</td>
                                    <td class="ledger-col-total ledger-total-paid" data-label="Collected">@if ($month['collected'] > 0)<x-rupees :amount="$month['collected']" />@else — @endif</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <th data-i18n="total">Total</th>
                                <td class="ledger-col-total">{{ $summary['joined'] }}</td>
                                <td class="ledger-col-total"><x-rupees :amount="$summary['joined_amount']" /></td>
                                <td class="ledger-col-total">{{ $summary['receipts'] }}</td>
                                <td class="ledger-col-total"><strong><x-rupees :amount="$summary['collected']" /></strong></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </section>

            {{-- Day by day: each day's total, then its receipts --}}
            <section class="group-panel glass payment-step">
                <div class="group-panel-header">
                    <h2 data-i18n="day_by_day_collections">Collections day by day</h2>
                </div>
                @if ($days->isEmpty())
                    <p class="members-empty" data-i18n="no_collections_period">Nothing collected in this period.</p>
                @else
                    <div class="ledger-table-wrapper">
                        <table class="ledger-table statement-table portal-table">
                            <thead>
                                <tr>
                                    <th data-i18n="time_word">Time</th>
                                    <th data-i18n="receipt_no">Receipt No.</th>
                                    <th data-i18n="customer_word">Customer</th>
                                    <th data-i18n="{{ $isFinance ? 'loan_no' : 'group_seat' }}">{{ $isFinance ? 'Loan No.' : 'Group · Seat' }}</th>
                                    <th data-i18n="payment_method">Payment method</th>
                                    <th class="ledger-col-total" data-i18n="amount_word">Amount</th>
                                </tr>
                            </thead>
                            @foreach ($days as $day)
                                <tbody>
                                    <tr class="agent-day-row">
                                        <th colspan="5">{{ $day['date']->format('D, d M Y') }} · {{ $day['receipts'] }} <span data-i18n="receipts_word">receipts</span></th>
                                        <td class="ledger-col-total ledger-total-paid" data-label="Total"><strong><x-rupees :amount="$day['amount']" /></strong></td>
                                    </tr>
                                    @foreach ($day['rows'] as $row)
                                        <tr>
                                            <td class="nowrap" data-label="Time">{{ $row['at']->format('h:i A') }}</td>
                                            <td class="nowrap" data-label="Receipt No."><a href="{{ $row['link'] }}">{{ $row['number'] }}</a></td>
                                            <td data-label="Customer"><x-customer-name :customer="$row['customer']" /></td>
                                            <td data-label="{{ $isFinance ? 'Loan No.' : 'Group · Seat' }}">{{ $row['reference'] }}</td>
                                            <td data-label="Payment method">{{ $row['method'] }}</td>
                                            <td class="ledger-col-total" data-label="Amount"><x-rupees :amount="$row['amount']" /></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            @endforeach
                            <tfoot>
                                <tr>
                                    <th colspan="5" data-i18n="total">Total</th>
                                    <td class="ledger-col-total"><strong><x-rupees :amount="$summary['collected']" /></strong></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                @endif
            </section>

        @endif

    </div>

</div>

@endsection
