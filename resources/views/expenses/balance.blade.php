@extends('layouts.app')

@section('title', 'Balance Sheet | SN Chit Funds')

@push('styles')
    @vite([
        'resources/css/create.css',
        'resources/css/groups.css',
        'resources/css/payments.css'
    ])
@endpush

@php
    $dates = $range === 'all' ? [] : ['from' => $from, 'to' => $to];
    $periodText = $range === 'all'
        ? 'All time'
        : \Illuminate\Support\Carbon::parse($from)->format('d M Y').' – '.\Illuminate\Support\Carbon::parse($to)->format('d M Y');
@endphp

@section('content')

<div class="groups-list-page payments-page balance-page">


    @if (session('success'))
        <div class="group-flash group-flash-success" role="status">
            <x-icon name="check" />
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="group-flash group-flash-error" role="alert">
            <x-icon name="info" />
            {{ $errors->first() }}
        </div>
    @endif


    {{-- =====================================================
         HEADER
    ====================================================== --}}

    <div class="groups-list-header">

        <div>
            <h1 class="groups-title" data-i18n="balance_sheet">Balance Sheet</h1>

            <p class="groups-subtitle" data-i18n="balance_sheet_subtitle">
                Business spending shared equally between the partners, and who pays whom to square up.
            </p>
        </div>

        <div class="ledger-actions">

            <a href="{{ route($routePrefix.'expenses.balance.print', $dates) }}" class="group-action" target="_blank" rel="noopener">
                <x-icon name="printer" />
                <span data-i18n="print_report">Print report</span>
            </a>

            <a href="{{ route($routePrefix.'expenses.balance.pdf', $dates) }}" class="add-group-button">
                <x-icon name="download" />
                <span data-i18n="download_pdf">Download PDF</span>
            </a>

        </div>

    </div>


    {{-- =====================================================
         PERIOD
    ====================================================== --}}

    <nav class="status-tabs payment-ranges" aria-label="Period">
        @foreach ($ranges as $rangeKey => $quick)
            @continue($rangeKey === 'today')
            <a
                href="{{ route($routePrefix.'expenses.balance', ['range' => $rangeKey]) }}"
                @class(['status-tab', 'active' => $range === $rangeKey])
            >
                <span data-i18n="{{ $quick['i18n'] }}">{{ $quick['label'] }}</span>
            </a>
        @endforeach
    </nav>

    <form method="GET" action="{{ route($routePrefix.'expenses.balance') }}" class="payment-filters payment-filters-grid">

        <label class="payment-filter">
            <span data-i18n="from_date">From</span>
            <input type="date" name="from" class="input payment-filter-select" value="{{ $range === 'all' ? '' : $from }}">
        </label>

        <label class="payment-filter">
            <span data-i18n="to_date">To</span>
            <input type="date" name="to" class="input payment-filter-select" value="{{ $range === 'all' ? '' : $to }}">
        </label>

        <button type="submit" class="group-action">
            <x-icon name="search" />
            <span data-i18n="show">Show</span>
        </button>

    </form>

    @if ($range !== 'all')
        <p class="partial-only-note balance-period-note">
            <x-icon name="info" />
            <span data-i18n="balance_period_note">These balances cover this period only. Choose "All time" for the running balance between the partners.</span>
        </p>
    @endif


    {{-- =====================================================
         TOTALS
    ====================================================== --}}

    <section class="payment-summary-bar glass">

        <div class="payment-summary-main">
            <span class="payment-summary-range">{{ $periodText }} · <span data-i18n="total_spent">Total spent</span></span>
            <strong class="payment-summary-total expense-total"><x-rupees :amount="$total" /></strong>
        </div>

        <div class="payment-summary-main">
            <span class="payment-summary-range">
                <span data-i18n="each_share">Each partner's share</span>
                ({{ $partners->count() }} <span data-i18n="partners_word">partners</span>)
            </span>
            <strong class="payment-summary-total"><x-rupees :amount="$share" /></strong>
        </div>

    </section>


    {{-- =====================================================
         PARTNERS
    ====================================================== --}}

    <section class="group-panel glass dues-list">

        <div class="group-panel-header">
            <h2 data-i18n="partners_heading">Partners</h2>
        </div>

        <div class="ledger-table-wrapper">
            <table class="ledger-table statement-table balance-table">
                <thead>
                    <tr>
                        <th data-i18n="partner_word">Partner</th>
                        <th class="ledger-col-total" data-i18n="spent_paid">Paid for expenses</th>
                        <th class="ledger-col-total" data-i18n="equal_share">Equal share</th>
                        <th class="ledger-col-total" data-i18n="settlement_given">Settlements given</th>
                        <th class="ledger-col-total" data-i18n="settlement_received">Settlements received</th>
                        <th class="ledger-col-total" data-i18n="net_put_in">Net put in</th>
                        <th class="ledger-col-total" data-i18n="balance">Balance</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($balances as $row)
                        <tr>
                            <td><strong>{{ $row['partner']->name }}</strong></td>
                            <td class="ledger-col-total">
                                <x-rupees :amount="$row['paid']" />
                                <small class="dues-part-paid">{{ $row['items'] }} <span data-i18n="expenses_word">expenses</span></small>
                            </td>
                            <td class="ledger-col-total"><x-rupees :amount="$row['share']" /></td>
                            <td class="ledger-col-total"><x-rupees :amount="$row['given']" /></td>
                            <td class="ledger-col-total"><x-rupees :amount="$row['received']" /></td>
                            <td class="ledger-col-total"><strong><x-rupees :amount="$row['net_put_in']" /></strong></td>
                            <td class="ledger-col-total">
                                @if ($row['balance'] >= 0.01)
                                    <strong class="balance-receive"><x-rupees :amount="$row['balance']" /></strong>
                                    <small class="balance-receive" data-i18n="to_receive">to receive</small>
                                @elseif ($row['balance'] <= -0.01)
                                    <strong class="balance-pay"><x-rupees :amount="-$row['balance']" /></strong>
                                    <small class="balance-pay" data-i18n="to_pay">to pay</small>
                                @else
                                    <strong class="balance-settled" data-i18n="settled_word">Settled</strong>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

    </section>


    {{-- =====================================================
         WHO PAYS WHOM + RECORD A SETTLEMENT
    ====================================================== --}}

    <section class="group-panel glass dues-list" id="settle">

        <div class="group-panel-header">
            <h2 data-i18n="who_pays_whom">Who pays whom</h2>
        </div>

        @forelse ($transfers as $transfer)
            <div class="balance-transfer">
                <span class="balance-transfer-text">
                    <strong>{{ $transfer['from']->name }}</strong>
                    <span data-i18n="pays_word">pays</span>
                    <strong>{{ $transfer['to']->name }}</strong>
                </span>
                <strong class="balance-transfer-amount"><x-rupees :amount="$transfer['amount']" /></strong>
                <a
                    href="{{ route($routePrefix.'expenses.balance', $dates + ['settle_from' => $transfer['from']->id, 'settle_to' => $transfer['to']->id, 'settle_amount' => (int) round($transfer['amount'])]) }}#settle-form"
                    class="group-action"
                >
                    <x-icon name="check" />
                    <span data-i18n="record_this_payment">Record this payment</span>
                </a>
            </div>
        @empty
            <p class="partial-only-note balance-even">
                <x-icon name="check" />
                <span data-i18n="all_square">All square — nobody owes anybody.</span>
            </p>
        @endforelse


        <form
            method="POST"
            action="{{ route($routePrefix.'expenses.settlements.store') }}"
            class="payment-form balance-settle-form"
            id="settle-form"
        >

            @csrf

            <h3 class="member-groups-title" data-i18n="record_settlement">Record a settlement</h3>

            <div class="group-form-grid payment-fields">

                <div class="group-field">
                    <label class="field-label" for="from_user_id" data-i18n="settlement_from">Paid by</label>
                    <select id="from_user_id" name="from_user_id" class="input" required>
                        @foreach ($partners as $partner)
                            <option value="{{ $partner->id }}" @selected((int) old('from_user_id', request('settle_from')) === $partner->id)>{{ $partner->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="group-field">
                    <label class="field-label" for="to_user_id" data-i18n="settlement_to">Paid to</label>
                    <select id="to_user_id" name="to_user_id" class="input" required>
                        @foreach ($partners as $partner)
                            <option value="{{ $partner->id }}" @selected((int) old('to_user_id', request('settle_to', $partners->skip(1)->first()?->id)) === $partner->id)>{{ $partner->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="group-field">
                    <label class="field-label" for="settle_amount" data-i18n="amount_rupees">Amount (₹)</label>
                    <input
                        type="text"
                        id="settle_amount"
                        name="amount"
                        value="{{ old('amount', request('settle_amount') ? number_format((int) request('settle_amount')) : '') }}"
                        class="input money-input"
                        inputmode="numeric"
                        required
                    >
                </div>

                <div class="group-field">
                    <label class="field-label" for="settled_on" data-i18n="expense_date">Date</label>
                    <input
                        type="date"
                        id="settled_on"
                        name="settled_on"
                        value="{{ old('settled_on', today(config('app.business_timezone'))->toDateString()) }}"
                        max="{{ today(config('app.business_timezone'))->toDateString() }}"
                        class="input"
                        required
                    >
                </div>

                <div class="group-field">
                    <span class="field-label" data-i18n="payment_method">Payment method</span>
                    <div class="method-options">
                        @foreach (\App\Models\Payment::METHODS as $methodValue => $methodLabel)
                            <label class="method-option">
                                <input type="radio" name="method" value="{{ $methodValue }}" @checked(old('method', 'cash') === $methodValue)>
                                <span data-i18n="method_{{ $methodValue }}">{{ $methodLabel }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <div class="group-field">
                    <label class="field-label" for="settle_reference" data-i18n="reference_number">Reference / UPI / cheque no.</label>
                    <input type="text" id="settle_reference" name="reference" value="{{ old('reference') }}" class="input" maxlength="100">
                </div>

                <div class="group-field group-field-full">
                    <label class="field-label" for="settle_notes" data-i18n="notes">Notes</label>
                    <input type="text" id="settle_notes" name="notes" value="{{ old('notes') }}" class="input" maxlength="1000">
                </div>

            </div>

            <div class="form-footer payment-form-footer">
                <button type="submit" class="save-button">
                    <x-icon name="save" />
                    <span data-i18n="save_settlement">Save settlement</span>
                </button>
            </div>

        </form>

    </section>


    {{-- =====================================================
         SETTLEMENTS SO FAR
    ====================================================== --}}

    <div class="balance-lower balance-lower-single">

        <section class="group-panel glass dues-list">

            <div class="group-panel-header">
                <h2 data-i18n="settlements_heading">Settlements</h2>
            </div>

            @forelse ($settlements as $settlement)
                <div class="balance-settlement">
                    <div>
                        <strong>{{ $settlement->fromUser->name }} → {{ $settlement->toUser->name }}</strong>
                        <small>
                            {{ $settlement->settled_on->format('d M Y') }} · {{ $settlement->methodLabel() }}
                            @if ($settlement->reference) · {{ $settlement->reference }} @endif
                            @if ($settlement->notes) · {{ $settlement->notes }} @endif
                        </small>
                    </div>
                    <strong><x-rupees :amount="$settlement->amount" /></strong>
                    <form
                        method="POST"
                        action="{{ route($routePrefix.'expenses.settlements.destroy', $settlement) }}"
                        onsubmit="return confirm('Delete this settlement of ₹{{ number_format($settlement->amount) }}?')"
                    >
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="balance-delete" aria-label="Delete settlement" title="Delete">
                            <x-icon name="trash" />
                        </button>
                    </form>
                </div>
            @empty
                <p class="members-empty" data-i18n="no_settlements">No settlements in this period.</p>
            @endforelse

        </section>


    </div>

</div>

@endsection
