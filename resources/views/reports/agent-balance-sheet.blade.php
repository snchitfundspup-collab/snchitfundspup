{{-- Agent Balance Sheet (Micro Finance): one agent's money in and out for a
     period — taken from / given to the treasury, from / to another agent,
     loans given and money collected, each with the customer. --}}
@extends('layouts.app')

@section('title', 'Agent Balance Sheet | Sri Lakshmi Micro Finance')

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

@section('content')

<div class="groups-list-page payments-page traders-page finance-page">

    <div class="groups-list-header">
        <div>
            <h1 class="groups-title" data-i18n="agent_balance_sheet">Agent Balance Sheet</h1>
            <p class="groups-subtitle" data-i18n="agent_balance_sheet_subtitle">Every rupee into and out of an agent's hand: from the treasury, lent to customers, collected back, between agents and returned.</p>
        </div>
    </div>

    @include('traders.partials.list-filters', [
        'route' => 'finance.reports.agent-balance',
        'routeParams' => [],
        'keep' => array_filter(['agent' => $agent?->id]),
        'searchPlaceholder' => null,
        'extra' => view('reports.partials.agent-filter', ['staff' => $staff, 'agent' => $agent])->render(),
    ])

    <div id="paymentsResults">

        <section class="payment-summary-bar glass">
            <div class="payment-summary-main">
                <span class="payment-summary-range">{{ $agent?->name }} · @include('traders.partials.range-label')</span>
                <strong @class(['payment-summary-total', 'ledger-total-due' => $summary['closing'] < 0])><x-rupees :amount="$summary['closing']" /></strong>
                <span class="payment-summary-count" data-i18n="in_hand_at_end">In hand at the end</span>
            </div>
            <div class="payment-summary-actions">
                <a href="{{ route('finance.reports.agent-balance.print', $query) }}" class="group-action" target="_blank" rel="noopener">
                    <x-icon name="printer" />
                    <span data-i18n="print_report">Print report</span>
                </a>
                <a href="{{ route('finance.reports.agent-balance.pdf', $query) }}" class="add-group-button">
                    <x-icon name="download" />
                    <span data-i18n="download_pdf">Download PDF</span>
                </a>
            </div>
        </section>

        <section class="group-panel glass">
            <table class="ledger-table statement-table profit-statement">
                <tbody>
                    <tr>
                        <th data-i18n="in_hand_at_start">In hand at the start</th>
                        <td class="ledger-col-total"><x-rupees :amount="$summary['opening']" /></td>
                    </tr>
                    <tr>
                        <th data-i18n="money_in_total">Money in (treasury, agents, collections)</th>
                        <td class="ledger-col-total ledger-total-paid">+ <x-rupees :amount="$summary['in']" /></td>
                    </tr>
                    <tr>
                        <th data-i18n="money_out_total">Money out (loans, treasury, agents)</th>
                        <td class="ledger-col-total">− <x-rupees :amount="$summary['out']" /></td>
                    </tr>
                    <tr class="profit-total">
                        <th data-i18n="in_hand_at_end">In hand at the end</th>
                        <td @class(['ledger-col-total', 'ledger-total-paid' => $summary['closing'] >= 0, 'ledger-total-due' => $summary['closing'] < 0])><strong><x-rupees :amount="$summary['closing']" /></strong></td>
                    </tr>
                </tbody>
            </table>
        </section>

        <section class="group-panel glass payment-step">
            <div class="group-panel-header">
                <h2 data-i18n="all_entries">All entries</h2>
            </div>

            @if ($rows->isEmpty())
                <p class="members-empty" data-i18n="no_entries_period_agent">Nothing in or out in this period.</p>
            @else
                <div class="ledger-table-wrapper">
                    <table class="ledger-table statement-table portal-table">
                        <thead>
                            <tr>
                                <th data-i18n="payment_date_time">Date &amp; time</th>
                                <th data-i18n="entry_word">Entry</th>
                                <th data-i18n="details">Details</th>
                                <th class="ledger-col-total" data-i18n="money_in">In</th>
                                <th class="ledger-col-total" data-i18n="money_out">Out</th>
                                <th class="ledger-col-total" data-i18n="in_hand">In hand</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($rows as $row)
                                <tr>
                                    <td class="nowrap" data-label="Date & time">{{ $row['at']->format('d M Y, h:i A') }}</td>
                                    <td data-label="Entry">
                                        @if ($row['link'])
                                            <a href="{{ $row['link'] }}">{{ $row['label'] }}</a>
                                        @else
                                            {{ $row['label'] }}
                                        @endif
                                    </td>
                                    <td data-label="Details">{{ $row['detail'] ?: '—' }}</td>
                                    <td class="ledger-col-total ledger-total-paid" data-label="In">@if ($row['in'] > 0)+ <x-rupees :amount="$row['in']" />@endif</td>
                                    <td class="ledger-col-total" data-label="Out">@if ($row['out'] > 0)− <x-rupees :amount="$row['out']" />@endif</td>
                                    <td @class(['ledger-col-total', 'ledger-total-due' => $row['balance'] < 0]) data-label="In hand"><strong><x-rupees :amount="$row['balance']" /></strong></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>

    </div>

</div>

@endsection
