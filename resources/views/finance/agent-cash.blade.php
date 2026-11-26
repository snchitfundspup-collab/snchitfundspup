{{-- Agent Cash: money in each agent's hand and in the treasury (owner), or
     the agent's own cash book (agent). --}}
@extends('layouts.app')

@section('title', 'Agent Cash | Sri Lakshmi Micro Finance')

@push('styles')
    @vite([
        'resources/css/create.css',
        'resources/css/groups.css',
        'resources/css/payments.css',
        'resources/css/traders.css',
        'resources/css/finance.css'
    ])
@endpush

@section('content')

<div class="group-show-page payments-page traders-page finance-page">

    @include('traders.partials.flash')

    <div class="groups-list-header">
        <div>
            <h1 class="groups-title" data-i18n="agent_cash">Agent Cash</h1>
            <p class="groups-subtitle" data-i18n="agent_cash_subtitle">Money in each agent's hand: taken from the treasury, lent to customers, collected back, passed between agents and returned.</p>
        </div>
    </div>


    {{-- WHERE THE MONEY IS (owner) --}}

    @if ($overview)
        <section class="dashboard-stats collection-stats">
            <div class="stat-tile glass">
                <span class="stat-icon icon-3d icon-3d-blue"><x-icon name="scale" /></span>
                <span class="stat-body">
                    <span class="stat-label" data-i18n="in_treasury">In the treasury</span>
                    <span @class(['stat-value', 'ledger-total-due' => $overview['treasury'] < 0])><x-rupees :amount="$overview['treasury']" /></span>
                    <span class="stat-note"><span data-i18n="available_to_lend">Available to lend</span> <x-rupees :amount="$overview['available']" /></span>
                </span>
            </div>
            @foreach ($overview['agents'] as $row)
                <a href="{{ route('finance.agent-cash.index', ['agent' => $row['agent']->id]) }}" @class(['stat-tile', 'glass', 'stat-tile-active' => $agent?->is($row['agent'])])>
                    <span class="stat-icon icon-3d icon-3d-green"><x-icon name="wallet" /></span>
                    <span class="stat-body">
                        <span class="stat-label">{{ $row['agent']->name }}</span>
                        <span @class(['stat-value', 'ledger-total-due' => $row['in_hand'] < 0])><x-rupees :amount="$row['in_hand']" /></span>
                        <span class="stat-note" data-i18n="in_hand">In hand</span>
                    </span>
                </a>
            @endforeach
        </section>


        {{-- RECORD (owner) --}}

        <section class="group-panel glass payment-step">

            <div class="group-panel-header">
                <h2 data-i18n="record_agent_cash">Record money given or taken</h2>
            </div>

            <form method="POST" action="{{ route('finance.agent-cash.store') }}" class="payment-form">

                @csrf

                <div class="group-form-grid payment-fields">

                    <div class="group-field group-field-full">
                        <span class="field-label" data-i18n="entry_type">Type</span>
                        <div class="method-options">
                            @foreach (\App\Models\FinanceAgentCash::TYPES as $typeValue => $typeLabel)
                                <label class="method-option">
                                    <input type="radio" name="type" value="{{ $typeValue }}" @checked(old('type', 'from_treasury') === $typeValue)>
                                    <span data-i18n="agent_cash_{{ $typeValue }}">{{ $typeLabel }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <div class="group-field">
                        <label class="field-label" for="agent_id" data-i18n="agent_word">Agent</label>
                        <select id="agent_id" name="agent_id" class="input" required>
                            @foreach ($staff as $person)
                                <option value="{{ $person->id }}" @selected((string) old('agent_id', $agent?->id) === (string) $person->id)>{{ $person->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="group-field">
                        <label class="field-label" for="other_agent_id" data-i18n="given_to_agent">Given to (agent to agent only)</label>
                        <select id="other_agent_id" name="other_agent_id" class="input">
                            <option value="">—</option>
                            @foreach ($staff as $person)
                                <option value="{{ $person->id }}" @selected((string) old('other_agent_id') === (string) $person->id)>{{ $person->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="group-field">
                        <label class="field-label" for="amount" data-i18n="amount_rupees">Amount (₹)</label>
                        <input type="text" id="amount" name="amount" class="input money-input" inputmode="numeric" required autocomplete="off" value="{{ old('amount') }}" placeholder="e.g. 10000">
                    </div>

                    <div class="group-field">
                        <label class="field-label" for="entry_at" data-i18n="payment_date_time">Date &amp; time</label>
                        <input type="datetime-local" id="entry_at" name="entry_at" class="input" required
                            value="{{ old('entry_at') ? str_replace(' ', 'T', old('entry_at')) : $now->format('Y-m-d\TH:i') }}"
                            max="{{ $now->format('Y-m-d\TH:i') }}">
                    </div>

                    <div class="group-field group-field-full">
                        <label class="field-label" for="notes" data-i18n="notes">Notes</label>
                        <input type="text" id="notes" name="notes" class="input" maxlength="1000" value="{{ old('notes') }}">
                    </div>

                    <p class="group-field group-field-full installment-hint" data-i18n="opening_balance_note">Opening balance: the money the agent has in hand right now. Their cash book starts again from it — loans and collections saved after it are counted.</p>

                </div>

                <div class="form-footer payment-form-footer">
                    <button type="submit" class="save-button">
                        <x-icon name="save" />
                        <span data-i18n="save_word">Save</span>
                    </button>
                </div>

            </form>

        </section>
    @endif


    {{-- CASH BOOK --}}

    <section class="group-panel glass">

        <div class="group-panel-header">
            <h2><span data-i18n="cash_book">Cash book</span>@if ($agent) · {{ $agent->name }}@endif</h2>
        </div>

        @if ($agent)
            <div class="finance-available">
                <small data-i18n="in_hand">In hand</small>
                <strong @class(['ledger-total-paid' => $inHand >= 0, 'ledger-total-due' => $inHand < 0])><x-rupees :amount="$inHand" /></strong>
            </div>
        @endif

        @if ($ledger->isEmpty())
            <p class="members-empty" data-i18n="no_cash_book_yet">No cash book yet. Record an opening balance or money taken from the treasury to start it.</p>
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
                            @can('finance-owner')
                                <th></th>
                            @endcan
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($ledger as $row)
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
                                @can('finance-owner')
                                    <td>
                                        @if ($row['entry'])
                                            <form method="POST" action="{{ route('finance.agent-cash.destroy', $row['entry']) }}" onsubmit="return confirm('Remove this entry?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="group-action receipt-cancel" aria-label="Remove"><x-icon name="trash" /></button>
                                            </form>
                                        @endif
                                    </td>
                                @endcan
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

    </section>

</div>

@endsection
