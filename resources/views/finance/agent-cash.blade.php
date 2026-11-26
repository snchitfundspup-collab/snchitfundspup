{{-- Agent Cash: money in each agent's hand and in the treasury, and the
     three things the owner records (taken from / given to the treasury,
     agent to agent). An agent sees only their own. --}}
@extends('layouts.app')

@section('title', 'Agent Cash | Sri Lakshmi Micro Finance')

@push('styles')
    @vite([
        'resources/css/create.css',
        'resources/css/dashboard.css',
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
            <p class="groups-subtitle" data-i18n="agent_cash_subtitle_short">How much money is with each agent and in the treasury.</p>
        </div>
        <div class="ledger-actions">
            <a href="{{ route('finance.reports.agent-balance') }}" class="group-action">
                <x-icon name="chart" />
                <span data-i18n="agent_balance_sheet">Agent Balance Sheet</span>
            </a>
        </div>
    </div>


    {{-- WHERE THE MONEY IS --}}

    <section class="dashboard-stats collection-stats">
        @if ($overview)
            <div class="stat-tile glass">
                <span class="stat-icon icon-3d icon-3d-blue"><x-icon name="scale" /></span>
                <span class="stat-body">
                    <span class="stat-label" data-i18n="in_treasury">In the treasury</span>
                    <span @class(['stat-value', 'ledger-total-due' => $overview['treasury'] < 0])><x-rupees :amount="$overview['treasury']" /></span>
                    <span class="stat-note"><span data-i18n="available_to_lend">Available to lend</span> <x-rupees :amount="$overview['available']" /></span>
                </span>
            </div>
            @foreach ($overview['agents'] as $row)
                <a href="{{ route('finance.reports.agent-balance', ['agent' => $row['agent']->id]) }}" class="stat-tile glass">
                    <span class="stat-icon icon-3d icon-3d-green"><x-icon name="wallet" /></span>
                    <span class="stat-body">
                        <span class="stat-label">{{ $row['agent']->name }}</span>
                        <span @class(['stat-value', 'ledger-total-due' => $row['in_hand'] < 0])><x-rupees :amount="$row['in_hand']" /></span>
                        <span class="stat-note" data-i18n="in_hand">In hand</span>
                    </span>
                </a>
            @endforeach
        @else
            <a href="{{ route('finance.reports.agent-balance') }}" class="stat-tile glass">
                <span class="stat-icon icon-3d icon-3d-green"><x-icon name="wallet" /></span>
                <span class="stat-body">
                    <span class="stat-label" data-i18n="cash_in_my_hand">Cash in my hand</span>
                    <span @class(['stat-value', 'ledger-total-due' => $myHand < 0])><x-rupees :amount="$myHand" /></span>
                </span>
            </a>
        @endif
    </section>


    {{-- RECORD (owner): three small forms --}}

    @if ($overview)
        <section class="agent-cash-forms">

            @foreach ([
                'from_treasury' => ['agent_cash_from_treasury', 'Taken from treasury', 'agent_cash_from_note', 'Money the agent takes from the treasury.', 'green'],
                'to_treasury' => ['agent_cash_to_treasury', 'Given to treasury', 'agent_cash_to_note', 'Money the agent puts back in the treasury.', 'blue'],
            ] as $typeValue => [$titleKey, $titleText, $noteKey, $noteText, $colour])
                <form method="POST" action="{{ route('finance.agent-cash.store') }}" class="group-panel glass payment-form agent-cash-form">
                    @csrf
                    <input type="hidden" name="type" value="{{ $typeValue }}">

                    <div class="group-panel-header">
                        <h2 data-i18n="{{ $titleKey }}">{{ $titleText }}</h2>
                    </div>
                    <p class="installment-hint" data-i18n="{{ $noteKey }}">{{ $noteText }}</p>

                    <div class="group-field">
                        <label class="field-label" for="{{ $typeValue }}_agent" data-i18n="agent_word">Agent</label>
                        <select id="{{ $typeValue }}_agent" name="agent_id" class="input" required>
                            <option value="">—</option>
                            @foreach ($staff as $person)
                                <option value="{{ $person->id }}" @selected(old('type') === $typeValue && (string) old('agent_id') === (string) $person->id)>{{ $person->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="group-field">
                        <label class="field-label" for="{{ $typeValue }}_amount" data-i18n="amount_rupees">Amount (₹)</label>
                        <input type="text" id="{{ $typeValue }}_amount" name="amount" class="input money-input" inputmode="numeric" required autocomplete="off" value="{{ old('type') === $typeValue ? old('amount') : '' }}">
                    </div>

                    <button type="submit" class="save-button">
                        <x-icon name="save" />
                        <span data-i18n="save_word">Save</span>
                    </button>
                </form>
            @endforeach

            <form method="POST" action="{{ route('finance.agent-cash.store') }}" class="group-panel glass payment-form agent-cash-form">
                @csrf
                <input type="hidden" name="type" value="transfer">

                <div class="group-panel-header">
                    <h2 data-i18n="agent_cash_transfer">Agent to agent</h2>
                </div>
                <p class="installment-hint" data-i18n="agent_cash_transfer_note">Money one agent gives to another.</p>

                <div class="group-field">
                    <label class="field-label" for="transfer_giver" data-i18n="giver_agent">Giver</label>
                    <select id="transfer_giver" name="agent_id" class="input" required>
                        <option value="">—</option>
                        @foreach ($staff as $person)
                            <option value="{{ $person->id }}" @selected(old('type') === 'transfer' && (string) old('agent_id') === (string) $person->id)>{{ $person->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="group-field">
                    <label class="field-label" for="transfer_receiver" data-i18n="receiver_agent">Receiver</label>
                    <select id="transfer_receiver" name="other_agent_id" class="input" required>
                        <option value="">—</option>
                        @foreach ($staff as $person)
                            <option value="{{ $person->id }}" @selected(old('type') === 'transfer' && (string) old('other_agent_id') === (string) $person->id)>{{ $person->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="group-field">
                    <label class="field-label" for="transfer_amount" data-i18n="amount_rupees">Amount (₹)</label>
                    <input type="text" id="transfer_amount" name="amount" class="input money-input" inputmode="numeric" required autocomplete="off" value="{{ old('type') === 'transfer' ? old('amount') : '' }}">
                </div>

                <button type="submit" class="save-button">
                    <x-icon name="save" />
                    <span data-i18n="save_word">Save</span>
                </button>
            </form>

        </section>
    @endif


    {{-- RECENT ENTRIES --}}

    <section class="group-panel glass">

        <div class="group-panel-header">
            <h2 data-i18n="recent_entries">Recent entries</h2>
        </div>

        @if ($recent->isEmpty())
            <p class="members-empty" data-i18n="no_agent_cash_yet">Nothing recorded yet.</p>
        @else
            <div class="ledger-table-wrapper">
                <table class="ledger-table statement-table portal-table">
                    <thead>
                        <tr>
                            <th data-i18n="payment_date_time">Date &amp; time</th>
                            <th data-i18n="entry_type">Type</th>
                            <th data-i18n="agent_word">Agent</th>
                            <th class="ledger-col-total" data-i18n="amount_word">Amount</th>
                            @can('finance-owner')
                                <th></th>
                            @endcan
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($recent as $entry)
                            <tr>
                                <td class="nowrap" data-label="Date & time">{{ $entry->entry_at->format('d M Y, h:i A') }}</td>
                                <td data-label="Type"><span data-i18n="agent_cash_{{ $entry->type }}">{{ $entry->typeLabel() }}</span></td>
                                <td data-label="Agent">
                                    {{ $entry->agent?->name }}@if ($entry->type === 'transfer') → {{ $entry->otherAgent?->name }}@endif
                                </td>
                                <td class="ledger-col-total" data-label="Amount"><x-rupees :amount="$entry->amount" /></td>
                                @can('finance-owner')
                                    <td>
                                        <form method="POST" action="{{ route('finance.agent-cash.destroy', $entry) }}" onsubmit="return confirm('Remove this entry?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="group-action receipt-cancel" aria-label="Remove"><x-icon name="trash" /></button>
                                        </form>
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
