@extends('layouts.app')

@section('title', 'Payments | SN Chit Funds')

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
        'method' => $filters['method'],
        'group' => $filters['group'],
    ]);

    $dates = ['from' => $filters['from'], 'to' => $filters['to']];

    $isDefault = $keep === [] && $filters['range'] === 'today';
@endphp

@section('content')

<div class="groups-list-page payments-page">


    {{-- =====================================================
         HEADER
    ====================================================== --}}

    <div class="groups-list-header">

        <div>

            <div class="groups-title-row">
                <h1
                    class="groups-title"
                    data-i18n="all_payments"
                >
                    All Payments
                </h1>
            </div>

            <p
                class="groups-subtitle"
                data-i18n="all_payments_subtitle"
            >
                Today's payments. Use the dates and filters to see earlier ones.
            </p>

        </div>

        <a
            href="{{ route('payments.create') }}"
            class="add-group-button"
        >
            <x-icon name="rupee" />
            <span data-i18n="collect_payment">Collect Payment</span>
        </a>

    </div>


    {{-- =====================================================
         FILTERS: quick ranges, then dates / group / method / search
    ====================================================== --}}

    <nav
        class="status-tabs payment-ranges"
        id="paymentRanges"
        aria-label="Date range"
    >
        @foreach ($ranges as $rangeKey => $quick)
            <a
                href="{{ route('payments.index', $keep + ['range' => $rangeKey]) }}"
                @class(['status-tab', 'active' => $filters['range'] === $rangeKey])
                @if ($filters['range'] === $rangeKey) aria-current="true" @endif
            >
                <span data-i18n="{{ $quick['i18n'] }}">{{ $quick['label'] }}</span>
            </a>
        @endforeach
    </nav>


    <form
        method="GET"
        action="{{ route('payments.index') }}"
        class="payment-filters payment-filters-grid"
        id="paymentFilterForm"
        role="search"
    >

        <label class="payment-filter">
            <span data-i18n="from_date">From</span>
            <input
                type="date"
                name="from"
                class="input payment-filter-select"
                value="{{ $filters['from'] }}"
                max="{{ today(config('app.business_timezone'))->toDateString() }}"
            >
        </label>

        <label class="payment-filter">
            <span data-i18n="to_date">To</span>
            <input
                type="date"
                name="to"
                class="input payment-filter-select"
                value="{{ $filters['to'] }}"
                max="{{ today(config('app.business_timezone'))->toDateString() }}"
            >
        </label>

        <label class="payment-filter">
            <span data-i18n="group_word">Group</span>
            <select
                name="group"
                class="input payment-filter-select"
            >
                <option value="" data-i18n="all_groups">All groups</option>
                @foreach ($groups as $option)
                    <option
                        value="{{ $option->id }}"
                        @selected($filters['group'] === $option->id)
                    >{{ $option->name }}</option>
                @endforeach
            </select>
        </label>

        <label class="payment-filter">
            <span data-i18n="payment_method">Method</span>
            <select
                name="method"
                class="input payment-filter-select"
            >
                <option value="" data-i18n="all_methods">All methods</option>
                @foreach (\App\Models\Payment::METHODS as $methodValue => $methodLabel)
                    <option
                        value="{{ $methodValue }}"
                        @selected($filters['method'] === $methodValue)
                        data-i18n="method_{{ $methodValue }}"
                    >{{ $methodLabel }}</option>
                @endforeach
            </select>
        </label>

        <div class="member-search payment-filter-search">

            <span class="member-search-icon">
                <x-icon name="search" />
            </span>

            <input
                type="search"
                name="q"
                class="member-search-input"
                value="{{ $filters['q'] }}"
                placeholder="Receipt no., customer or group"
                data-i18n-placeholder="payment_filter_placeholder"
                autocomplete="off"
            >

        </div>

        <a
            href="{{ route('payments.index') }}"
            class="group-action"
            id="paymentFilterClear"
            @if ($isDefault) hidden @endif
        >
            <x-icon name="x" />
            <span data-i18n="back_to_today">Back to today</span>
        </a>

    </form>


    {{-- results (summary + list) are swapped in place by payments.js while filtering --}}

    <div id="paymentsResults">


        {{-- =================================================
             SUMMARY of what is filtered + print / PDF
        ================================================== --}}

        <section class="payment-summary-bar glass">

            <div class="payment-summary-main">

                <span class="payment-summary-range">
                    @if ($filters['receipt_lookup'])
                        <span data-i18n="receipt_word">Receipt</span> {{ strtoupper($filters['q']) }}
                    @elseif ($filters['from'] === $filters['to'])
                        {{ \Illuminate\Support\Carbon::parse($filters['from'])->format('D, d M Y') }}
                    @else
                        {{ \Illuminate\Support\Carbon::parse($filters['from'])->format('d M Y') }}
                        – {{ \Illuminate\Support\Carbon::parse($filters['to'])->format('d M Y') }}
                    @endif
                    @if ($filters['group_name'])
                        · {{ $filters['group_name'] }}
                    @endif
                </span>

                <strong class="payment-summary-total"><x-rupees :amount="$summary['total']" /></strong>

                <span class="payment-summary-count">
                    {{ $summary['count'] }} <span data-i18n="receipts">receipts</span>
                </span>

            </div>

            @if ($summary['by_method'] !== [])
                <div class="payment-summary-methods">
                    @foreach ($summary['by_method'] as $methodKey => $methodTotal)
                        <span class="payment-method-total">
                            <span data-i18n="method_{{ $methodKey }}">{{ \App\Models\Payment::METHODS[$methodKey] }}</span>
                            <strong><x-rupees :amount="$methodTotal['amount']" /></strong>
                            <small>({{ $methodTotal['count'] }})</small>
                        </span>
                    @endforeach
                </div>
            @endif

            @if ($summary['count'] > 0)
                <div class="payment-summary-actions">

                    <a
                        href="{{ route('payments.print', $keep + $dates) }}"
                        class="group-action"
                        target="_blank"
                        rel="noopener"
                    >
                        <x-icon name="printer" />
                        <span data-i18n="print_list">Print list</span>
                    </a>

                    <a
                        href="{{ route('payments.pdf', $keep + $dates) }}"
                        class="add-group-button"
                    >
                        <x-icon name="download" />
                        <span data-i18n="download_pdf">Download PDF</span>
                    </a>

                </div>
            @endif

        </section>


        {{-- =================================================
             LIST
        ================================================== --}}

        @if ($payments->isEmpty())

            <div class="groups-empty glass">

                <span class="groups-empty-icon icon-3d icon-3d-green">
                    <x-icon name="rupee" />
                </span>

                @if ($isDefault)
                    <strong data-i18n="no_payments_today">No payments today yet</strong>
                    <p data-i18n="no_payments_today_text">Choose another date or range above to see earlier payments.</p>
                @else
                    <strong data-i18n="no_payments">No payments found</strong>
                @endif

            </div>

        @else

            <div class="payment-list">

                @foreach ($payments as $payment)

                    <a
                        href="{{ route('payments.show', $payment) }}"
                        class="payment-row glass"
                    >

                        <x-customer-avatar :customer="$payment->customer" />

                        <div class="payment-row-main">
                            <strong><x-customer-name :customer="$payment->customer" /></strong>
                            <span>
                                {{ $payment->member->member_code }} · {{ $payment->chitGroup->name }}
                                · {{ $payment->monthsCoveredLabel() }}
                            </span>
                        </div>

                        <div class="payment-row-meta">
                            <span class="payment-method-tag">{{ $payment->methodLabel() }}</span>
                            <span>{{ $payment->receipt_number }} · {{ $payment->paid_at->format('d M Y, h:i A') }}</span>
                        </div>

                        <strong class="payment-row-amount">
                            <x-rupees :amount="$payment->amount" />
                        </strong>

                    </a>

                @endforeach

            </div>


            <x-pagination :paginator="$payments" label="Payment pages" />

        @endif

    </div>

</div>

@endsection


@push('scripts')
    @vite('resources/js/payments.js')
@endpush
