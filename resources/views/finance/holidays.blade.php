{{-- Holidays (owner): dates when chosen agents' customers pay nothing. --}}
@extends('layouts.app')

@section('title', 'Holidays | Sri Lakshmi Micro Finance')

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
            <h1 class="groups-title" data-i18n="holidays_word">Holidays</h1>
            <p class="groups-subtitle" data-i18n="holidays_subtitle">Festival days when an agent's customers pay nothing. Instalments on those days move later, and the later ones with them — no one becomes overdue.</p>
        </div>
    </div>


    {{-- ADD --}}

    <section class="group-panel glass payment-step">

        <div class="group-panel-header">
            <h2 data-i18n="add_holiday">Add a holiday</h2>
        </div>

        <form method="POST" action="{{ route('finance.holidays.store') }}" class="payment-form">

            @csrf

            <div class="group-form-grid payment-fields">

                <div class="group-field">
                    <label class="field-label" for="starts_on" data-i18n="from_date">From</label>
                    <input type="date" id="starts_on" name="starts_on" class="input" required value="{{ old('starts_on', $today->toDateString()) }}">
                </div>

                <div class="group-field">
                    <label class="field-label" for="ends_on" data-i18n="to_date">To</label>
                    <input type="date" id="ends_on" name="ends_on" class="input" required value="{{ old('ends_on', $today->copy()->addDays(6)->toDateString()) }}">
                </div>

                <div class="group-field group-field-full">
                    <span class="field-label" data-i18n="agents_word">Agents</span>
                    <div class="method-options">
                        @foreach ($staff as $person)
                            <label class="method-option">
                                <input type="checkbox" name="agent_ids[]" value="{{ $person->id }}" @checked(in_array((string) $person->id, array_map('strval', (array) old('agent_ids', [])), true))>
                                <span>{{ $person->name }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <div class="group-field group-field-full">
                    <label class="field-label" for="notes" data-i18n="notes">Notes</label>
                    <input type="text" id="notes" name="notes" class="input" maxlength="255" value="{{ old('notes') }}" placeholder="e.g. Pongal" data-i18n-placeholder="holiday_note_placeholder">
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


    {{-- LIST --}}

    <section class="group-panel glass">

        <div class="group-panel-header">
            <h2 data-i18n="holidays_word">Holidays</h2>
        </div>

        @if ($holidays->isEmpty())
            <p class="members-empty" data-i18n="no_holidays_yet">No holidays yet.</p>
        @else
            <div class="ledger-table-wrapper">
                <table class="ledger-table statement-table portal-table">
                    <thead>
                        <tr>
                            <th data-i18n="from_date">From</th>
                            <th data-i18n="to_date">To</th>
                            <th data-i18n="agent_word">Agent</th>
                            <th data-i18n="notes">Notes</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($holidays as $holiday)
                            <tr>
                                <td class="nowrap" data-label="From">{{ $holiday->starts_on->format('D, d M Y') }}</td>
                                <td class="nowrap" data-label="To">{{ $holiday->ends_on->format('D, d M Y') }}</td>
                                <td data-label="Agent">{{ $holiday->agent?->name }}</td>
                                <td data-label="Notes">{{ $holiday->notes ?: '—' }}</td>
                                <td>
                                    <form method="POST" action="{{ route('finance.holidays.destroy', $holiday) }}" onsubmit="return confirm('Remove this holiday? The due dates go back as before.')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="group-action receipt-cancel" aria-label="Remove"><x-icon name="trash" /></button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

    </section>

</div>

@endsection
