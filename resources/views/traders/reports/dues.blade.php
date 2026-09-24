@extends('layouts.app')

@section('title', 'Customer Dues | SN Traders')

@push('styles')
    @vite([
        'resources/css/groups.css',
        'resources/css/payments.css',
        'resources/css/traders.css'
    ])
@endpush

@push('scripts')
    @vite('resources/js/payments.js')
@endpush

@php
    $bucketLabels = [
        'b30' => ['age_0_30', '0–30 days'],
        'b60' => ['age_31_60', '31–60 days'],
        'b90' => ['age_61_90', '61–90 days'],
        'over90' => ['age_over_90', 'Over 90 days'],
    ];
    $ageOptions = [
        'all' => ['everyone_owing', 'Everyone who owes'],
        '30' => ['older_30', 'Unpaid over 30 days'],
        '60' => ['older_60', 'Unpaid over 60 days'],
        '90' => ['older_90', 'Unpaid over 90 days'],
    ];
@endphp

@section('content')

<div class="groups-list-page payments-page traders-page">

    @include('traders.reports.partials.page-top', [
        'titleKey' => 'customer_dues',
        'title' => 'Customer Dues',
        'subtitleKey' => 'customer_dues_subtitle',
        'subtitle' => 'Who has to pay and how long it has been unpaid. Money received pays off the oldest invoices first.',
    ])


    <form
        method="GET"
        action="{{ route('traders.reports.show', 'dues') }}"
        class="payment-filters payment-filters-grid"
        id="paymentFilterForm"
        role="search"
    >
        <label class="payment-filter">
            <span data-i18n="show_word">Show</span>
            <select name="age" class="input payment-filter-select">
                @foreach ($ageOptions as $ageKey => [$ageI18n, $ageLabel])
                    <option value="{{ $ageKey }}" @selected($age === $ageKey) data-i18n="{{ $ageI18n }}">{{ $ageLabel }}</option>
                @endforeach
            </select>
        </label>

        <div class="member-search payment-filter-search">
            <span class="member-search-icon"><x-icon name="search" /></span>
            <input type="search" name="q" class="member-search-input" value="{{ $search }}" placeholder="Name, ID, phone or identification" autocomplete="off">
        </div>

        <a
            href="{{ route('traders.reports.show', 'dues') }}"
            class="group-action"
            id="paymentFilterClear"
            @if ($age === 'all' && $search === '') hidden @endif
        >
            <x-icon name="x" />
            <span data-i18n="clear">Clear</span>
        </a>
    </form>


    <div id="paymentsResults">

        <section class="payment-summary-bar glass dues-summary">
            <div class="payment-summary-main">
                <span class="payment-summary-range">
                    <span data-i18n="customers_owe_total">Customers owe</span> · {{ $today->format('d M Y') }}
                </span>
                <strong class="payment-summary-total dues-total-pending"><x-rupees :amount="$summary['total']" /></strong>
                <span class="payment-summary-count">{{ $summary['count'] }} <span data-i18n="customers_word">customers</span></span>
            </div>
            <div class="payment-summary-methods">
                @foreach ($bucketLabels as $bucket => [$bucketI18n, $bucketLabel])
                    <span class="payment-method-total">
                        <span data-i18n="{{ $bucketI18n }}">{{ $bucketLabel }}</span>
                        <strong @class(['ledger-total-due' => $bucket !== 'b30' && $summary['buckets'][$bucket] > 0])><x-rupees :amount="$summary['buckets'][$bucket]" /></strong>
                    </span>
                @endforeach
            </div>
            @include('traders.reports.partials.actions')
        </section>


        @if ($rows->isEmpty())

            <div class="groups-empty glass">
                <span class="groups-empty-icon icon-3d icon-3d-green"><x-icon name="check" /></span>
                <strong data-i18n="nobody_owes">Nobody on this list.</strong>
            </div>

        @else

            <section class="group-panel glass">
                <div class="ledger-table-wrapper">
                    <table class="ledger-table statement-table dues-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th data-i18n="customer_word">Customer</th>
                                <th data-i18n="oldest_unpaid">Oldest unpaid</th>
                                <th data-i18n="last_paid">Last paid</th>
                                @foreach ($bucketLabels as [$bucketI18n, $bucketLabel])
                                    <th class="ledger-col-total" data-i18n="{{ $bucketI18n }}">{{ $bucketLabel }}</th>
                                @endforeach
                                <th class="ledger-col-total" data-i18n="balance">Balance</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($rows as $row)
                                @php $customer = $row['customer']; @endphp
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>
                                        <a href="{{ route('traders.accounts.show', $customer) }}" class="dues-member-link">
                                            <strong><x-customer-name :customer="$customer" /></strong>
                                        </a>
                                        <span class="dues-member-code">{{ $customer->customer_code }} · {{ $customer->phone ?: '—' }}</span>
                                    </td>
                                    <td>
                                        @if ($row['oldest_unpaid'])
                                            {{ \Illuminate\Support\Carbon::parse($row['oldest_unpaid'])->format('d M Y') }}
                                            <span @class(['due-badge', 'due-badge-due' => $row['days'] > 30, 'due-badge-month' => $row['days'] <= 30])>
                                                {{ $row['days'] }} <span data-i18n="days_word">days</span>
                                            </span>
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td>{{ $row['last_paid'] ? \Illuminate\Support\Carbon::parse($row['last_paid'])->format('d M Y') : '—' }}</td>
                                    @foreach (array_keys($bucketLabels) as $bucket)
                                        <td class="ledger-col-total">
                                            @if ($row['buckets'][$bucket] > 0)
                                                <x-rupees :amount="$row['buckets'][$bucket]" />
                                            @else
                                                —
                                            @endif
                                        </td>
                                    @endforeach
                                    <td class="ledger-col-total ledger-total-due"><strong><x-rupees :amount="$row['balance']" /></strong></td>
                                    <td class="dues-actions">
                                        <div class="master-actions">
                                            <a href="{{ route('traders.receipts.create', ['customer' => $customer->id]) }}" class="quick-collect-button" title="Receive payment" aria-label="Receive payment from {{ $customer->name }}">
                                                <x-icon name="rupee" />
                                            </a>
                                            @if ($customer->phone)
                                                <a href="tel:{{ preg_replace('/[^\d+]/', '', $customer->phone) }}" class="member-call-button collect-call-button" aria-label="Call {{ $customer->name }}">
                                                    <x-icon name="phone" />
                                                    <span data-i18n="call">Call</span>
                                                </a>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="4"><span data-i18n="total">Total</span> ({{ $rows->count() }})</th>
                                @foreach (array_keys($bucketLabels) as $bucket)
                                    <td class="ledger-col-total"><x-rupees :amount="$rows->sum(fn ($row) => $row['buckets'][$bucket])" /></td>
                                @endforeach
                                <td class="ledger-col-total"><strong><x-rupees :amount="$summary['shown_total']" /></strong></td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </section>

        @endif

    </div>

</div>

@endsection
