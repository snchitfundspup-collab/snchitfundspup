@extends('layouts.app')

@section('title', 'Expenses | SN Chit Funds')

@push('styles')
    @vite([
        'resources/css/groups.css',
        'resources/css/payments.css'
    ])
@endpush

@php
    /* the filters kept when switching range or printing */
    $keep = array_filter([
        'q' => $filters['q'],
        'partner' => $filters['partner'],
    ]);

    $dates = ['from' => $filters['from'], 'to' => $filters['to']];
@endphp

@section('content')

<div class="groups-list-page payments-page expenses-page">


    @if (session('success'))
        <div class="group-flash group-flash-success" role="status">
            <x-icon name="check" />
            {{ session('success') }}
        </div>
    @endif


    {{-- =====================================================
         HEADER
    ====================================================== --}}

    <div class="groups-list-header">

        <div>
            <h1 class="groups-title" data-i18n="all_expenses">All Expenses</h1>

            <p class="groups-subtitle" data-i18n="expenses_subtitle">
                Business spending by the partners. This month by default.
            </p>
        </div>

        <div class="ledger-actions">

            <a
                href="{{ route($routePrefix.'expenses.balance') }}"
                class="group-action"
            >
                <x-icon name="scale" />
                <span data-i18n="balance_sheet">Balance Sheet</span>
            </a>

            <a
                href="{{ route($routePrefix.'expenses.create') }}"
                class="add-group-button"
            >
                <x-icon name="plus" />
                <span data-i18n="add_expense">Add Expense</span>
            </a>

        </div>

    </div>


    {{-- =====================================================
         FILTERS
    ====================================================== --}}

    <nav
        class="status-tabs payment-ranges"
        id="paymentRanges"
        aria-label="Date range"
    >
        @foreach ($ranges as $rangeKey => $quick)
            <a
                href="{{ route($routePrefix.'expenses.index', $keep + ['range' => $rangeKey]) }}"
                @class(['status-tab', 'active' => $filters['range'] === $rangeKey])
            >
                <span data-i18n="{{ $quick['i18n'] }}">{{ $quick['label'] }}</span>
            </a>
        @endforeach
    </nav>


    <form
        method="GET"
        action="{{ route($routePrefix.'expenses.index') }}"
        class="payment-filters payment-filters-grid"
        id="paymentFilterForm"
        role="search"
    >

        <label class="payment-filter">
            <span data-i18n="from_date">From</span>
            <input type="date" name="from" class="input payment-filter-select" value="{{ $filters['from'] }}">
        </label>

        <label class="payment-filter">
            <span data-i18n="to_date">To</span>
            <input type="date" name="to" class="input payment-filter-select" value="{{ $filters['to'] }}">
        </label>

        <label class="payment-filter">
            <span data-i18n="paid_by_partner">Paid by</span>
            <select name="partner" class="input payment-filter-select">
                <option value="" data-i18n="both_partners">All partners</option>
                @foreach ($partners as $partner)
                    <option value="{{ $partner->id }}" @selected($filters['partner'] === $partner->id)>{{ $partner->name }}</option>
                @endforeach
            </select>
        </label>

        <div class="member-search payment-filter-search">
            <span class="member-search-icon"><x-icon name="search" /></span>
            <input
                type="search"
                name="q"
                class="member-search-input"
                value="{{ $filters['q'] }}"
                placeholder="What for, paid to, bill no."
                data-i18n-placeholder="expense_search_placeholder"
                autocomplete="off"
            >
        </div>

        <a
            href="{{ route($routePrefix.'expenses.index') }}"
            class="group-action"
            id="paymentFilterClear"
            @if ($keep === [] && $filters['range'] === 'month') hidden @endif
        >
            <x-icon name="x" />
            <span data-i18n="clear">Clear</span>
        </a>

    </form>


    <div id="paymentsResults">


        {{-- =================================================
             SUMMARY + PRINT / PDF
        ================================================== --}}

        <section class="payment-summary-bar glass">

            <div class="payment-summary-main">
                <span class="payment-summary-range">
                    @if ($filters['range'] === 'all')
                        <span data-i18n="range_all">All time</span>
                    @elseif ($filters['from'] === $filters['to'])
                        {{ \Illuminate\Support\Carbon::parse($filters['from'])->format('D, d M Y') }}
                    @else
                        {{ \Illuminate\Support\Carbon::parse($filters['from'])->format('d M Y') }} – {{ \Illuminate\Support\Carbon::parse($filters['to'])->format('d M Y') }}
                    @endif
                </span>
                <strong class="payment-summary-total expense-total"><x-rupees :amount="$summary['total']" /></strong>
                <span class="payment-summary-count">{{ $summary['count'] }} <span data-i18n="expenses_word">expenses</span></span>
            </div>

            @if ($summary['count'] > 0)
                <div class="payment-summary-methods">
                    @foreach ($summary['by_partner'] as $byPartner)
                        <span class="payment-method-total">
                            {{ $byPartner['name'] }}
                            <strong><x-rupees :amount="$byPartner['amount']" /></strong>
                            <small>({{ $byPartner['count'] }})</small>
                        </span>
                    @endforeach
                </div>

                <div class="payment-summary-actions">
                    <a href="{{ route($routePrefix.'expenses.print', $keep + $dates) }}" class="group-action" target="_blank" rel="noopener">
                        <x-icon name="printer" />
                        <span data-i18n="print_list">Print list</span>
                    </a>
                    <a href="{{ route($routePrefix.'expenses.pdf', $keep + $dates) }}" class="add-group-button">
                        <x-icon name="download" />
                        <span data-i18n="download_pdf">Download PDF</span>
                    </a>
                </div>
            @endif

        </section>


        {{-- =================================================
             LIST
        ================================================== --}}

        @if ($expenses->isEmpty())

            <div class="groups-empty glass">
                <span class="groups-empty-icon icon-3d icon-3d-green"><x-icon name="wallet" /></span>
                <strong data-i18n="no_expenses">No expenses in this period</strong>
                <a href="{{ route($routePrefix.'expenses.create') }}" class="add-group-button">
                    <x-icon name="plus" />
                    <span data-i18n="add_expense">Add Expense</span>
                </a>
            </div>

        @else

            <div class="payment-list">

                @foreach ($expenses as $expense)

                    <a href="{{ route($routePrefix.'expenses.edit', $expense) }}" class="payment-row glass">

                        <span class="draw-row-icon icon-3d icon-3d-green expense-row-icon">
                            <x-icon name="wallet" />
                        </span>

                        <div class="payment-row-main">
                            <strong>{{ $expense->description }}</strong>
                            <span>
                                {{ $expense->paid_to ?: '—' }}
                                @if ($expense->reference) · <span data-i18n="bill_short">Bill</span> {{ $expense->reference }} @endif
                            </span>
                        </div>

                        <div class="payment-row-meta">
                            <span class="payment-method-tag">{{ $expense->payer->name }}</span>
                            <span>{{ $expense->methodLabel() }} · {{ $expense->spent_on->format('d M Y') }}</span>
                        </div>

                        <strong class="payment-row-amount"><x-rupees :amount="$expense->amount" /></strong>

                    </a>

                @endforeach

            </div>

            <x-pagination :paginator="$expenses" label="Expense pages" />

        @endif

    </div>

</div>

@endsection


@push('scripts')
    @vite('resources/js/payments.js')
@endpush
