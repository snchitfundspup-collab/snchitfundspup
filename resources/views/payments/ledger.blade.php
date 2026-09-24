@extends('layouts.app')

@section('title', 'Payment Ledger'.($group ? ' – '.$group->name : '').' | SN Chit Funds')

@push('styles')
    @vite([
        'resources/css/groups.css',
        'resources/css/payments.css'
    ])
@endpush

@section('content')

<div class="groups-list-page payments-page ledger-page">


    {{-- =====================================================
         SCREEN HEADER + FILTERS
    ====================================================== --}}

    <div class="groups-list-header no-print">

        <div>
            <h1
                class="groups-title"
                data-i18n="payment_ledger"
            >
                Payment Ledger
            </h1>

            <p
                class="groups-subtitle"
                data-i18n="ledger_subtitle"
            >
                Group-wise, month-wise payments for every member.
            </p>
        </div>

        @if ($group)
            <div class="ledger-actions">

                <button
                    type="button"
                    class="group-action"
                    onclick="window.print()"
                >
                    <x-icon name="printer" />
                    <span data-i18n="print_ledger">Print ledger</span>
                </button>

                <a
                    href="{{ route('payments.ledger.pdf', ['group' => $group->id, 'from' => $fromMonth, 'to' => $toMonth]) }}"
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

            <span class="groups-empty-icon icon-3d icon-3d-green">
                <x-icon name="chart" />
            </span>

            <strong data-i18n="ledger_no_groups">No started groups yet</strong>

            <p data-i18n="ledger_no_groups_text">The ledger shows groups once they have started.</p>

        </div>

    @else

        <form
            method="GET"
            action="{{ route('payments.ledger') }}"
            class="payment-filters ledger-filters no-print"
        >

            <label class="ledger-filter">
                <span data-i18n="group_word">Group</span>
                <select
                    name="group"
                    class="input payment-filter-select"
                    onchange="this.form.from.value = ''; this.form.to.value = ''; this.form.submit()"
                >
                    @foreach ($groups as $option)
                        <option
                            value="{{ $option->id }}"
                            @selected($option->id === $group->id)
                        >{{ $option->name }}</option>
                    @endforeach
                </select>
            </label>

            <label class="ledger-filter">
                <span data-i18n="from_month">From month</span>
                <input
                    type="number"
                    name="from"
                    class="input payment-filter-select ledger-month-input"
                    value="{{ $fromMonth }}"
                    min="1"
                    max="{{ $group->months }}"
                >
            </label>

            <label class="ledger-filter">
                <span data-i18n="to_month">To month</span>
                <input
                    type="number"
                    name="to"
                    class="input payment-filter-select ledger-month-input"
                    value="{{ $toMonth }}"
                    min="1"
                    max="{{ $group->months }}"
                >
            </label>

            <button
                type="submit"
                class="group-action"
            >
                <x-icon name="search" />
                <span data-i18n="show">Show</span>
            </button>

        </form>


        {{-- =================================================
             LEDGER SHEET (this is what prints)
        ================================================== --}}

        <section class="ledger-sheet glass">

            {{-- print-only letterhead --}}

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
                    <strong data-i18n="payment_ledger">Payment Ledger</strong>
                    <span>{{ now(config('app.business_timezone'))->format('d M Y, h:i A') }}</span>
                </div>

            </header>


            {{-- group summary --}}

            <div class="ledger-group-info">

                <div>
                    <span data-i18n="group_word">Group</span>
                    <strong>{{ $group->name }}</strong>
                </div>

                <div>
                    <span data-i18n="monthly_installment">Monthly Installment</span>
                    <strong><x-rupees :amount="$group->installment_amount" /></strong>
                </div>

                <div>
                    <span data-i18n="months_shown">Months</span>
                    <strong>{{ $fromMonth }}–{{ $toMonth }} <span data-i18n="of">of</span> {{ $group->months }}</strong>
                </div>

                <div>
                    <span data-i18n="collected">Collected</span>
                    <strong class="ledger-total-paid"><x-rupees :amount="$totalPaid" /></strong>
                </div>

                <div>
                    <span data-i18n="due_now">Due now</span>
                    <strong @class(['ledger-total-due' => $totalPending > 0, 'ledger-total-open' => $totalPending === 0 && $totalDue > 0])><x-rupees :amount="$totalDue" /></strong>
                </div>

            </div>


            @if ($rows->isEmpty())

                <p
                    class="members-empty"
                    data-i18n="members_empty"
                >
                    No members yet.
                </p>

            @else

                <div class="ledger-table-wrapper">

                    <table class="ledger-table">

                        <thead>
                            <tr>
                                <th class="ledger-sticky ledger-col-no">#</th>
                                <th class="ledger-sticky ledger-col-member" data-i18n="group_members">Member</th>

                                @foreach ($monthNumbers as $month)
                                    <th class="ledger-month-head">
                                        <span>M{{ $month }}</span>
                                        <small>{{ $group->monthPeriodLabel($month, withYear: false) }}</small>
                                    </th>
                                @endforeach

                                <th class="ledger-col-total" data-i18n="paid">Paid</th>
                                <th class="ledger-col-total" data-i18n="due_now">Due now</th>
                                <th class="ledger-col-prize" data-i18n="prize_won">Prize won</th>
                            </tr>
                        </thead>

                        <tbody>

                            @foreach ($rows as $row)

                                <tr>
                                    <td class="ledger-sticky ledger-col-no">{{ $loop->iteration }}</td>

                                    <td class="ledger-sticky ledger-col-member">
                                        <strong><x-customer-name :customer="$row['member']->customer" /></strong>
                                        <span>{{ $row['member']->member_code }}</span>
                                    </td>

                                    @foreach ($monthNumbers as $month)

                                        @php
                                            $cell = $row['cells'][$month];
                                            $wonThisMonth = $row['won'] && $row['won']->month_number === $month;
                                        @endphp

                                        <td
                                            @class(['ledger-cell', 'ledger-cell-'.$cell['status'], 'ledger-cell-won' => $wonThisMonth])
                                            title="Month {{ $month }}: paid {{ number_format($cell['paid']) }} of {{ number_format($cell['installment']) }}{{ $wonThisMonth ? ' · won the draw' : '' }}"
                                        >
                                            @if ($wonThisMonth)
                                                <span class="ledger-won-mark" title="Prize money won this month">
                                                    +{{ \Illuminate\Support\Number::format($row['won']->prizeAmount(), locale: 'en_IN') }}
                                                </span>
                                            @endif

                                            @if ($cell['paid'] > 0)
                                                {{ \Illuminate\Support\Number::format($cell['paid'], locale: 'en_IN') }}
                                            @elseif ($cell['status'] === 'due')
                                                —
                                            @endif
                                        </td>

                                    @endforeach

                                    <td class="ledger-col-total"><x-rupees :amount="$row['paid']" /></td>

                                    <td @class(['ledger-col-total', 'ledger-total-due' => $row['pending'] > 0, 'ledger-total-open' => $row['pending'] === 0 && $row['balance_due'] > 0])>
                                        <x-rupees :amount="$row['balance_due']" />
                                    </td>

                                    <td class="ledger-col-prize">
                                        @if ($row['won'])
                                            <a
                                                href="{{ route('draws.show', $row['won']) }}"
                                                class="ledger-prize"
                                            >
                                                <strong><x-rupees :amount="$row['won']->prizeAmount()" /></strong>
                                                <small>
                                                    M{{ $row['won']->month_number }} ·
                                                    @if ($row['won']->isPaidOut())
                                                        <span class="ledger-prize-paid" data-i18n="payout_paid">Paid out</span>
                                                    @else
                                                        <span class="ledger-prize-pending" data-i18n="payout_pending">Awaiting payout</span>
                                                    @endif
                                                </small>
                                            </a>
                                        @else
                                            <span class="ledger-prize-none">—</span>
                                        @endif
                                    </td>
                                </tr>

                            @endforeach

                        </tbody>

                        <tfoot>
                            <tr>
                                <th class="ledger-sticky ledger-col-no"></th>
                                <th class="ledger-sticky ledger-col-member" data-i18n="total">Total</th>

                                @foreach ($monthNumbers as $month)
                                    <td class="ledger-month-total">
                                        <strong>{{ \Illuminate\Support\Number::format($monthTotals[$month]['paid'], locale: 'en_IN') }}</strong>
                                        <small>/ {{ \Illuminate\Support\Number::format($monthTotals[$month]['expected'], locale: 'en_IN') }}</small>
                                    </td>
                                @endforeach

                                <td class="ledger-col-total"><x-rupees :amount="$totalPaid" /></td>
                                <td @class(['ledger-col-total', 'ledger-total-due' => $totalPending > 0, 'ledger-total-open' => $totalPending === 0 && $totalDue > 0])><x-rupees :amount="$totalDue" /></td>
                                <td class="ledger-col-prize"><strong><x-rupees :amount="$totalPrizes" /></strong></td>
                            </tr>
                        </tfoot>

                    </table>

                </div>


                {{-- key --}}

                <div class="ledger-key">
                    <span><i class="ledger-swatch ledger-cell-paid"></i> <span data-i18n="ledger_paid">Paid</span></span>
                    <span><i class="ledger-swatch ledger-cell-partial"></i> <span data-i18n="ledger_partial">Part paid</span></span>
                    <span><i class="ledger-swatch ledger-cell-due"></i> — <span data-i18n="ledger_due">Pending</span></span>
                    <span><i class="ledger-swatch ledger-cell-upcoming"></i> <span data-i18n="ledger_upcoming">Upcoming</span></span>
                    <span><i class="ledger-swatch ledger-cell-won"></i> <span class="ledger-won-key">+₹</span> <span data-i18n="ledger_won">Prize money won that month</span></span>
                    <span class="ledger-key-note" data-i18n="ledger_key_note">Amounts in ₹. Month totals show collected / expected.</span>
                </div>

            @endif

        </section>

    @endif

</div>

@endsection
