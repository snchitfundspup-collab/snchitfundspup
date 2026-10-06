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
                <tr>
                    <th>
                        <span data-i18n="lent_in_hand">Lent (in hand)</span>
                        <small class="dues-part-paid" data-i18n="lent_in_hand_note">Loan amount less the fee and GST kept</small>
                    </th>
                    <td class="ledger-col-total">− <x-rupees :amount="$position['lent']" /></td>
                </tr>
                <tr>
                    <th data-i18n="collected_word">Collected</th>
                    <td class="ledger-col-total ledger-total-paid">+ <x-rupees :amount="$position['collected']" /></td>
                </tr>
                <tr>
                    <th data-i18n="menu_expenses">Expenses</th>
                    <td class="ledger-col-total">− <x-rupees :amount="$position['expenses']" /></td>
                </tr>
                <tr class="profit-total">
                    <th data-i18n="available_to_lend">Available to lend</th>
                    <td @class(['ledger-col-total', 'ledger-total-paid' => $position['available'] >= 0, 'ledger-total-due' => $position['available'] < 0])><strong><x-rupees :amount="$position['available']" /></strong></td>
                </tr>
            </tbody>
        </table>

    </section>


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
