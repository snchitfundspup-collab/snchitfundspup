@extends('layouts.app')

@section('title', 'Capital & Cash | Sri Lakshmi Micro Finance')

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
            <h1 class="groups-title" data-i18n="capital_cash">Capital &amp; Cash</h1>
            <p class="groups-subtitle" data-i18n="capital_subtitle">Money put into the business to lend, and how much is available to lend now.</p>
        </div>
    </div>


    {{-- AVAILABLE TO LEND --}}

    <section class="group-panel glass">

        <div class="finance-available">
            <small data-i18n="available_to_lend">Available to lend</small>
            <strong @class(['ledger-total-paid' => $position['available'] >= 0, 'ledger-total-due' => $position['available'] < 0])><x-rupees :amount="$position['available']" /></strong>
        </div>

        <table class="ledger-table statement-table profit-statement">
            <tbody>
                <tr>
                    <th data-i18n="capital_invested">Capital invested</th>
                    <td class="ledger-col-total">+ <x-rupees :amount="$position['invested']" /></td>
                </tr>
                <tr>
                    <th data-i18n="capital_withdrawn">Capital taken out</th>
                    <td class="ledger-col-total">− <x-rupees :amount="$position['withdrawn']" /></td>
                </tr>
                {{-- money given out, by the agent who brought the customers in --}}
                @foreach ($agents as $row)
                    <tr>
                        <th>
                            <span data-i18n="given_to_customers_of">Given to customers of</span> {{ $row['agent'] }}
                            <small class="dues-part-paid">
                                {{ $row['loans'] }} <span data-i18n="loans_word">loans</span> · <x-rupees :amount="$row['principal']" />
                                − <span data-i18n="processing_fee">Processing fee</span> <x-rupees :amount="$row['fee'] + $row['gst']" />
                            </small>
                        </th>
                        <td class="ledger-col-total">− <x-rupees :amount="$row['in_hand']" /></td>
                    </tr>
                @endforeach
                @if ($agents->isEmpty())
                    <tr>
                        <th data-i18n="lent_in_hand">Lent (in hand)</th>
                        <td class="ledger-col-total">− <x-rupees :amount="0" /></td>
                    </tr>
                @endif

                {{-- money that came back --}}
                @foreach ($agents as $row)
                    <tr>
                        <th><span data-i18n="collected_from_customers_of">Collected back from customers of</span> {{ $row['agent'] }}</th>
                        <td class="ledger-col-total ledger-total-paid">+ <x-rupees :amount="$row['collected']" /></td>
                    </tr>
                @endforeach
                <tr>
                    <th data-i18n="menu_expenses">Expenses</th>
                    <td class="ledger-col-total">− <x-rupees :amount="$position['expenses']" /></td>
                </tr>
                <tr class="profit-total">
                    <th data-i18n="money_in_hand_to_lend">Money in hand to lend again</th>
                    <td @class(['ledger-col-total', 'ledger-total-paid' => $position['available'] >= 0, 'ledger-total-due' => $position['available'] < 0])><strong><x-rupees :amount="$position['available']" /></strong></td>
                </tr>
            </tbody>
        </table>

        {{-- for reference: what stayed with the business, and what is still out --}}
        <div class="finance-breakdown">
            <span><small data-i18n="processing_fee_profit">Processing fee kept (profit)</small><strong class="ledger-total-paid"><x-rupees :amount="(int) $agents->sum('fee')" /></strong></span>
            @if ($agents->sum('gst') > 0)
                <span><small data-i18n="gst_kept">GST kept (owed to the government)</small><strong><x-rupees :amount="(int) $agents->sum('gst')" /></strong></span>
            @endif
            <span><small data-i18n="still_with_customers">Still with customers (to collect)</small><strong class="finance-due-value"><x-rupees :amount="(int) $agents->sum('to_collect')" /></strong></span>
        </div>

    </section>


    {{-- BY AGENT --}}

    @if ($agents->isNotEmpty())
        <section class="group-panel glass payment-step">
            <div class="group-panel-header">
                <h2 data-i18n="money_by_agent">Money by agent</h2>
            </div>
            <div class="ledger-table-wrapper">
                <table class="ledger-table statement-table portal-table">
                    <thead>
                        <tr>
                            <th data-i18n="agent_word">Agent</th>
                            <th class="ledger-col-total" data-i18n="loans_heading">Loans</th>
                            <th class="ledger-col-total" data-i18n="loan_amount_word">Loan amount</th>
                            <th class="ledger-col-total" data-i18n="processing_fee">Processing fee</th>
                            <th class="ledger-col-total" data-i18n="given_in_hand">Given in hand</th>
                            <th class="ledger-col-total" data-i18n="collected_word">Collected</th>
                            <th class="ledger-col-total" data-i18n="still_to_collect">Still to collect</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($agents as $row)
                            <tr>
                                <td data-label="Agent"><strong>{{ $row['agent'] }}</strong></td>
                                <td class="ledger-col-total" data-label="Loans">{{ $row['loans'] }}</td>
                                <td class="ledger-col-total" data-label="Loan amount"><x-rupees :amount="$row['principal']" /></td>
                                <td class="ledger-col-total" data-label="Processing fee"><x-rupees :amount="$row['fee'] + $row['gst']" /></td>
                                <td class="ledger-col-total" data-label="Given in hand"><x-rupees :amount="$row['in_hand']" /></td>
                                <td class="ledger-col-total ledger-total-paid" data-label="Collected"><x-rupees :amount="$row['collected']" /></td>
                                <td @class(['ledger-col-total', 'ledger-total-due' => $row['to_collect'] > 0]) data-label="Still to collect"><x-rupees :amount="$row['to_collect']" /></td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <th data-i18n="total">Total</th>
                            <td class="ledger-col-total">{{ $agents->sum('loans') }}</td>
                            <td class="ledger-col-total"><x-rupees :amount="(int) $agents->sum('principal')" /></td>
                            <td class="ledger-col-total"><x-rupees :amount="(int) ($agents->sum('fee') + $agents->sum('gst'))" /></td>
                            <td class="ledger-col-total"><x-rupees :amount="(int) $agents->sum('in_hand')" /></td>
                            <td class="ledger-col-total"><x-rupees :amount="(int) $agents->sum('collected')" /></td>
                            <td class="ledger-col-total"><x-rupees :amount="(int) $agents->sum('to_collect')" /></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </section>
    @endif


    {{-- ADD --}}

    <section class="group-panel glass payment-step">

        <div class="group-panel-header">
            <h2 data-i18n="add_capital">Invest or take out capital</h2>
        </div>

        <form method="POST" action="{{ route('finance.capital.store') }}" class="payment-form">

            @csrf

            <div class="group-form-grid payment-fields">

                <div class="group-field">
                    <span class="field-label" data-i18n="entry_type">Type</span>
                    <div class="method-options">
                        @foreach (\App\Models\FinanceCapital::TYPES as $typeValue => $typeLabel)
                            <label class="method-option">
                                <input type="radio" name="type" value="{{ $typeValue }}" @checked(old('type', 'invest') === $typeValue)>
                                <span data-i18n="capital_{{ $typeValue }}">{{ $typeLabel }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <div class="group-field">
                    <label class="field-label" for="amount" data-i18n="amount_rupees">Amount (₹)</label>
                    <input type="text" id="amount" name="amount" class="input money-input" inputmode="numeric" required autocomplete="off" value="{{ old('amount') }}" placeholder="e.g. 500000">
                </div>

                <div class="group-field">
                    <label class="field-label" for="entry_on" data-i18n="date">Date</label>
                    <input type="date" id="entry_on" name="entry_on" class="input" required value="{{ old('entry_on', $today->toDateString()) }}" max="{{ $today->toDateString() }}">
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

                <div class="group-field group-field-full">
                    <label class="field-label" for="notes" data-i18n="notes">Notes</label>
                    <input type="text" id="notes" name="notes" class="input" maxlength="1000" value="{{ old('notes') }}">
                </div>

            </div>

            <div class="form-footer payment-form-footer">
                <button type="submit" class="save-button">
                    <x-icon name="save" />
                    <span data-i18n="save_word">Save</span>
                </button>
            </div>

        </form>

    </section>


    {{-- ENTRIES --}}

    <section class="group-panel glass">

        <div class="group-panel-header">
            <h2 data-i18n="capital_entries">Capital entries</h2>
        </div>

        @if ($entries->isEmpty())
            <p class="members-empty" data-i18n="no_capital_yet">No capital recorded yet. Add the money you are putting into the business above.</p>
        @else
            <div class="ledger-table-wrapper">
                <table class="ledger-table statement-table portal-table">
                    <thead>
                        <tr>
                            <th data-i18n="date">Date</th>
                            <th data-i18n="entry_type">Type</th>
                            <th data-i18n="notes">Notes</th>
                            <th data-i18n="recorded_by">Recorded by</th>
                            <th class="ledger-col-total" data-i18n="amount_word">Amount</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($entries as $entry)
                            <tr>
                                <td class="nowrap" data-label="Date">{{ $entry->entry_on->format('d M Y') }}</td>
                                <td data-label="Type"><span @class(['due-badge', 'due-badge-ok' => $entry->type === 'invest', 'due-badge-month' => $entry->type === 'withdraw']) data-i18n="capital_{{ $entry->type }}">{{ $entry->typeLabel() }}</span></td>
                                <td data-label="Notes">{{ $entry->notes ?: '—' }}</td>
                                <td data-label="Recorded by">{{ $entry->recorder?->name ?? '—' }}</td>
                                <td @class(['ledger-col-total', 'ledger-total-paid' => $entry->type === 'invest']) data-label="Amount">{{ $entry->type === 'invest' ? '+' : '−' }} <x-rupees :amount="$entry->amount" /></td>
                                <td>
                                    <form method="POST" action="{{ route('finance.capital.destroy', $entry) }}" onsubmit="return confirm('Delete this entry?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="group-action receipt-cancel" aria-label="Delete"><x-icon name="trash" /></button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <x-pagination :paginator="$entries" label="Capital pages" />
        @endif

    </section>

</div>

@endsection
