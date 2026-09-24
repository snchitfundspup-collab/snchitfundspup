@extends('layouts.app')

@section('title', 'Customer Balances | SN Traders')

@push('styles')
    @vite([
        'resources/css/groups.css',
        'resources/css/payments.css',
        'resources/css/traders.css'
    ])
@endpush

@php
    $query = array_filter(['view' => $view === 'owing' ? null : $view, 'q' => $search]);
@endphp

@section('content')

<div class="groups-list-page payments-page traders-page">

    @include('traders.partials.flash')


    <div class="groups-list-header">
        <div>
            <h1 class="groups-title" data-i18n="customer_balances">Customer Balances</h1>
            <p class="groups-subtitle" data-i18n="balances_subtitle">What each customer owes SN Traders — sales minus money received.</p>
        </div>
        <div class="ledger-actions">
            <a href="{{ route('traders.balances.print', $query) }}" class="group-action" target="_blank" rel="noopener">
                <x-icon name="printer" />
                <span data-i18n="print_report">Print report</span>
            </a>
            <a href="{{ route('traders.balances.pdf', $query) }}" class="add-group-button">
                <x-icon name="download" />
                <span data-i18n="download_pdf">Download PDF</span>
            </a>
        </div>
    </div>


    <form method="GET" action="{{ route('traders.balances.index') }}" class="payment-filters payment-filters-grid">

        <nav class="status-tabs collect-tabs" aria-label="Which customers">
            @foreach (['owing' => ['customers_owing', 'Owe money'], 'advance' => ['advances_word', 'Advances'], 'all' => ['status_all', 'All']] as $tabView => [$tabKey, $tabLabel])
                <a
                    href="{{ route('traders.balances.index', array_filter(['view' => $tabView === 'owing' ? null : $tabView, 'q' => $search])) }}"
                    @class(['status-tab', 'active' => $view === $tabView])
                >
                    <span data-i18n="{{ $tabKey }}">{{ $tabLabel }}</span>
                </a>
            @endforeach
        </nav>

        @if ($view !== 'owing')
            <input type="hidden" name="view" value="{{ $view }}">
        @endif

        <div class="member-search payment-filter-search">
            <span class="member-search-icon"><x-icon name="search" /></span>
            <input type="search" name="q" class="member-search-input" value="{{ $search }}" placeholder="Name, ID, phone or identification" autocomplete="off">
        </div>

    </form>


    <section class="payment-summary-bar glass dues-summary">
        <div class="payment-summary-main">
            <span class="payment-summary-range" data-i18n="customers_owe_total">Customers owe</span>
            <strong class="payment-summary-total dues-total-pending"><x-rupees :amount="$totalOwing" /></strong>
            <span class="payment-summary-count">{{ $owingCount }} <span data-i18n="customers_word">customers</span></span>
        </div>
        @if ($totalAdvance > 0)
            <div class="payment-summary-main">
                <span class="payment-summary-range" data-i18n="advances_held">Advances held</span>
                <strong class="payment-summary-total"><x-rupees :amount="$totalAdvance" /></strong>
            </div>
        @endif
        <div class="payment-summary-actions">
            <a href="{{ route('traders.receipts.create') }}" class="add-group-button">
                <x-icon name="rupee" />
                <span data-i18n="receive_payment">Receive Payment</span>
            </a>
        </div>
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
                            <th data-i18n="last_sale">Last sale</th>
                            <th class="ledger-col-total" data-i18n="sales_word">Sales</th>
                            <th class="ledger-col-total" data-i18n="received_word">Received</th>
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
                                <td>{{ $row['last_sale'] ? \Illuminate\Support\Carbon::parse($row['last_sale'])->format('d M Y') : '—' }}</td>
                                <td class="ledger-col-total"><x-rupees :amount="$row['sales']" /></td>
                                <td class="ledger-col-total"><x-rupees :amount="$row['received']" /></td>
                                <td @class(['ledger-col-total', 'ledger-total-due' => $row['balance'] > 0])>
                                    @if ($row['balance'] < 0)
                                        <strong><x-rupees :amount="-$row['balance']" /></strong> <small data-i18n="advance_word">advance</small>
                                    @else
                                        <strong><x-rupees :amount="$row['balance']" /></strong>
                                    @endif
                                </td>
                                <td class="dues-actions">
                                    <div class="master-actions">
                                        @if ($row['balance'] > 0)
                                            <a href="{{ route('traders.receipts.create', ['customer' => $customer->id]) }}" class="quick-collect-button" title="Receive payment" aria-label="Receive payment from {{ $customer->name }}">
                                                <x-icon name="rupee" />
                                            </a>
                                        @endif
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
                </table>
            </div>
        </section>

    @endif

</div>

@endsection
