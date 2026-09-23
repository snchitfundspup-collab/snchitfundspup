@extends('layouts.app')

@section('title', 'Winners Report'.($selectedGroup ? ' – '.$selectedGroup->name : '').' | SN Chit Funds')

@push('styles')
    @vite([
        'resources/css/groups.css',
        'resources/css/payments.css',
        'resources/css/draws.css'
    ])
@endpush

@section('content')

<div class="groups-list-page payments-page winners-page">


    {{-- =====================================================
         SCREEN HEADER + FILTER
    ====================================================== --}}

    <div class="groups-list-header no-print">

        <div>
            <a
                href="{{ route('draws.index') }}"
                class="group-back-link"
            >
                <x-icon name="arrow-left" />
                <span data-i18n="back_to_draw_details">Back to Draw Details</span>
            </a>

            <h1
                class="groups-title"
                data-i18n="winners_report"
            >
                Winners Report
            </h1>

            <p
                class="groups-subtitle"
                data-i18n="winners_report_subtitle"
            >
                Month-wise draw winners for every group.
            </p>
        </div>

        @if ($reportGroups->isNotEmpty())
            <div class="ledger-actions">

                <button
                    type="button"
                    class="group-action"
                    onclick="window.print()"
                >
                    <x-icon name="printer" />
                    <span data-i18n="print_report">Print report</span>
                </button>

                <a
                    href="{{ route('draws.winners.pdf', array_filter(['group' => $selectedGroup?->id])) }}"
                    class="add-group-button"
                >
                    <x-icon name="download" />
                    <span data-i18n="download_pdf">Download PDF</span>
                </a>

            </div>
        @endif

    </div>


    @if ($groups->isEmpty())

        <div class="groups-empty glass">

            <span class="groups-empty-icon icon-3d icon-3d-orange">
                <x-icon name="trophy" />
            </span>

            <strong data-i18n="no_draws">No draws yet</strong>

            <a
                href="{{ route('draws.create') }}"
                class="add-group-button"
            >
                <x-icon name="trophy" />
                <span data-i18n="run_draw">Run Draw</span>
            </a>

        </div>

    @else

        <form
            method="GET"
            action="{{ route('draws.winners') }}"
            class="payment-filters ledger-filters no-print"
        >

            <label class="ledger-filter">
                <span data-i18n="group_word">Group</span>
                <select
                    name="group"
                    class="input payment-filter-select"
                    onchange="this.form.submit()"
                >
                    <option value="" data-i18n="all_groups">All groups</option>
                    @foreach ($groups as $option)
                        <option
                            value="{{ $option->id }}"
                            @selected($option->id === $selectedGroup?->id)
                        >{{ $option->name }}</option>
                    @endforeach
                </select>
            </label>

        </form>


        {{-- =================================================
             REPORT SHEET (this is what prints)
        ================================================== --}}

        <section class="ledger-sheet winners-sheet glass">

            <header class="ledger-print-head">

                <img
                    class="receipt-logo"
                    src="{{ asset('images/sn-chit-funds-logo.png') }}"
                    alt="SN Chit Funds"
                >

                <div>
                    <strong class="receipt-company"><span>SN</span> Chit Funds</strong>
                    <span class="receipt-tagline">Trust · Growth · Together</span>
                </div>

                <div class="ledger-print-title">
                    <strong data-i18n="winners_report">Winners Report</strong>
                    <span>{{ now(config('app.business_timezone'))->format('d M Y, h:i A') }}</span>
                </div>

            </header>


            <div class="ledger-group-info">

                <div>
                    <span data-i18n="group_word">Group</span>
                    <strong>{{ $selectedGroup?->name ?? 'All groups' }}</strong>
                </div>

                <div>
                    <span data-i18n="draws_held">Draws held</span>
                    <strong>{{ $drawCount }}</strong>
                </div>

                <div>
                    <span data-i18n="total_prize">Total prize</span>
                    <strong class="ledger-total-paid"><x-rupees :amount="$totalPrize" /></strong>
                </div>

                <div>
                    <span data-i18n="payout_paid">Paid out</span>
                    <strong><x-rupees :amount="$totalPaidOut" /></strong>
                </div>

            </div>


            @foreach ($reportGroups as $reportGroup)

                <div class="winners-group">

                    <h2 class="winners-group-title">
                        {{ $reportGroup->name }}
                        <small>
                            <x-rupees :amount="$reportGroup->amount" /> ·
                            {{ $reportGroup->draws->count() }} <span data-i18n="of">of</span> {{ $reportGroup->months }}
                            <span data-i18n="months_drawn">months drawn</span>
                        </small>
                    </h2>

                    <div class="ledger-table-wrapper">

                        <table class="ledger-table winners-table">

                            <thead>
                                <tr>
                                    <th data-i18n="month_number">Month</th>
                                    <th data-i18n="period">Period</th>
                                    <th class="winners-col-name" data-i18n="winner">Winner</th>
                                    <th data-i18n="phone">Phone</th>
                                    <th class="ledger-col-total" data-i18n="prize_word">Prize</th>
                                    <th data-i18n="drawn_on">Drawn on</th>
                                    <th data-i18n="payout_word">Payout</th>
                                </tr>
                            </thead>

                            <tbody>

                                @foreach ($reportGroup->draws as $draw)

                                    <tr>
                                        <td><strong>M{{ $draw->month_number }}</strong></td>

                                        <td>{{ $reportGroup->monthPeriodLabel($draw->month_number) }}</td>

                                        <td class="winners-col-name">
                                            <a href="{{ route('draws.show', $draw) }}">
                                                <strong><x-customer-name :customer="$draw->winner->customer" /></strong>
                                            </a>
                                            <span>{{ $draw->winner->member_code }}</span>
                                        </td>

                                        <td>{{ $draw->winner->customer->phone ?: '—' }}</td>

                                        <td class="ledger-col-total"><x-rupees :amount="$draw->prizeAmount()" /></td>

                                        <td>{{ $draw->drawn_at->format('d M Y') }}</td>

                                        <td>
                                            @if ($draw->isPaidOut())
                                                <span class="ledger-prize-paid" data-i18n="payout_paid">Paid out</span>
                                                <small class="winners-payout-meta">
                                                    {{ $draw->voucher_number }} · {{ $draw->paid_at->format('d M Y') }} · {{ $draw->payoutMethodLabel() }}
                                                </small>
                                            @else
                                                <span class="ledger-prize-pending" data-i18n="payout_pending">Awaiting payout</span>
                                            @endif
                                        </td>
                                    </tr>

                                @endforeach

                            </tbody>

                            <tfoot>
                                <tr>
                                    <th colspan="4" data-i18n="total">Total</th>
                                    <td class="ledger-col-total">
                                        <x-rupees :amount="$reportGroup->draws->sum(fn ($draw) => $draw->prizeAmount())" />
                                    </td>
                                    <td colspan="2"></td>
                                </tr>
                            </tfoot>

                        </table>

                    </div>

                </div>

            @endforeach

        </section>

    @endif

</div>

@endsection
