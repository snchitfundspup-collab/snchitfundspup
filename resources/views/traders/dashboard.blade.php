@extends('layouts.app')

@section('title', 'Dashboard | SN Traders')

@push('styles')
    @vite([
        'resources/css/dashboard.css',
        'resources/css/traders.css'
    ])
@endpush

@php
    $localNow = now(config('app.business_timezone'));

    [$greetingKey, $greeting] = match (true) {
        $localNow->hour < 12 => ['greeting_morning', 'Good morning'],
        $localNow->hour < 17 => ['greeting_afternoon', 'Good afternoon'],
        default => ['greeting_evening', 'Good evening'],
    };

    $kg = fn ($value) => rtrim(rtrim(number_format((float) $value, 2), '0'), '.');
@endphp

@section('content')

<div class="dashboard-page">


    {{-- =====================================================
         WELCOME
    ====================================================== --}}

    <section class="dashboard-welcome glass">

        <div class="dashboard-welcome-glow"></div>

        <div class="dashboard-welcome-text">
            <div class="dashboard-date">
                <x-icon name="calendar" />
                {{ $localNow->format('l, j F Y') }}
            </div>

            <h1 class="dashboard-title">
                <span data-i18n="{{ $greetingKey }}">{{ $greeting }}</span>,
                <span class="dashboard-name">{{ auth()->user()->name }}</span>
            </h1>

            <p class="dashboard-subtitle" data-i18n="traders_dashboard_subtitle">
                Here's what's happening with SN Traders today.
            </p>
        </div>

        <div class="dashboard-actions">

            <a href="{{ route('traders.sales.create') }}" class="dashboard-action dashboard-action-primary">
                <x-icon name="rupee" />
                <span data-i18n="new_sale">New Sale</span>
            </a>

            <a href="{{ route('traders.purchases.create') }}" class="dashboard-action">
                <x-icon name="package" />
                <span data-i18n="new_purchase">New Purchase</span>
            </a>

            <a href="{{ route('traders.receipts.create') }}" class="dashboard-action">
                <x-icon name="check" />
                <span data-i18n="receive_payment">Receive Payment</span>
            </a>

            <a href="{{ route('dashboard') }}" class="dashboard-action business-jump">
                <x-icon name="layers" />
                <span><span class="business-switch-sn">SN</span> Chit Funds</span>
            </a>

        </div>

    </section>


    {{-- =====================================================
         MONEY CARDS
    ====================================================== --}}

    <section class="dashboard-stats collection-stats">

        <a href="{{ route('traders.sales.index', ['range' => 'today']) }}" class="stat-tile glass">
            <span class="stat-icon icon-3d icon-3d-green"><x-icon name="rupee" /></span>
            <span class="stat-body">
                <span class="stat-label" data-i18n="sales_today">Sales today</span>
                <span class="stat-value"><x-rupees :amount="$salesToday" /></span>
                <span class="stat-note">{{ $salesTodayCount }} <span data-i18n="{{ $salesTodayCount === 1 ? 'invoice_word' : 'invoices_word' }}">{{ $salesTodayCount === 1 ? 'invoice' : 'invoices' }}</span> · <span data-i18n="received_word">received</span> <x-rupees :amount="$receivedToday" /></span>
            </span>
        </a>

        <a href="{{ route('traders.sales.index', ['range' => 'month']) }}" class="stat-tile glass">
            <span class="stat-icon icon-3d icon-3d-blue"><x-icon name="calendar" /></span>
            <span class="stat-body">
                <span class="stat-label"><span data-i18n="sales_this_month">Sales this month</span> · {{ $localNow->format('M Y') }}</span>
                <span class="stat-value"><x-rupees :amount="$salesMonth" /></span>
                <span class="stat-note"><span data-i18n="received_word">received</span> <x-rupees :amount="$receivedMonth" /></span>
            </span>
        </a>

        <a href="{{ route('traders.purchases.index', ['range' => 'month']) }}" class="stat-tile glass">
            <span class="stat-icon icon-3d icon-3d-purple"><x-icon name="package" /></span>
            <span class="stat-body">
                <span class="stat-label" data-i18n="purchases_this_month">Purchases this month</span>
                <span class="stat-value"><x-rupees :amount="$purchasesMonth" /></span>
                <span class="stat-note" data-i18n="rice_bought_note">rice bought from suppliers</span>
            </span>
        </a>

        <a href="{{ route('traders.balances.index') }}" class="stat-tile glass stat-tile-pending">
            <span class="stat-icon icon-3d icon-3d-red"><x-icon name="info" /></span>
            <span class="stat-body">
                <span class="stat-label" data-i18n="customer_credit">Customer credit</span>
                <span class="stat-value stat-value-due"><x-rupees :amount="$creditTotal" /></span>
                <span class="stat-note">{{ $creditCount }} <span data-i18n="{{ $creditCount === 1 ? 'customer_owes_money' : 'customers_owe_money' }}">{{ $creditCount === 1 ? 'customer owes money' : 'customers owe money' }}</span></span>
            </span>
        </a>

    </section>


    {{-- =====================================================
         STOCK + WHO OWES
    ====================================================== --}}

    <section class="dashboard-card glass">

        <div class="dashboard-card-header">
            <h2 data-i18n="menu_stock">Stock</h2>
            <a href="{{ route('traders.stock') }}" class="dashboard-card-link">
                <span data-i18n="view_all">View all</span>
                <x-icon name="arrow-right" />
            </a>
        </div>

        <div class="draw-lists">

            <div>
                <h3 class="draw-list-title" data-i18n="rice_in_stock">Rice in stock</h3>
                @forelse ($stock as $row)
                    <div class="draw-list-row">
                        <div class="draw-list-main">
                            <strong>{{ $row['name'] }}</strong>
                            <span>{{ $kg($row['bag_kg']) }} <span data-i18n="kg_bag">kg bag</span></span>
                        </div>
                        <strong @class(['draw-list-amount', 'stock-negative' => $row['stock_bags'] < 0])>{{ $row['stock_bags'] }} <span data-i18n="bags_lower">bags</span></strong>
                    </div>
                @empty
                    <p class="dashboard-empty draw-list-empty">
                        <a href="{{ route('traders.varieties.index') }}" data-i18n="add_rice_varieties">Add your rice varieties to start</a>
                    </p>
                @endforelse
            </div>

            <div>
                <h3 class="draw-list-title" data-i18n="who_owes_most">Who owes the most</h3>
                @forelse ($topOwing as $row)
                    <a href="{{ route('traders.accounts.show', $row['customer']) }}" class="draw-list-row">
                        <div class="draw-list-main">
                            <strong><x-customer-name :customer="$row['customer']" /></strong>
                            <span>{{ $row['customer']->customer_code }} · {{ $row['customer']->phone ?: '—' }}</span>
                        </div>
                        <strong class="draw-list-amount stock-negative"><x-rupees :amount="$row['balance']" /></strong>
                    </a>
                @empty
                    <p class="dashboard-empty draw-list-empty" data-i18n="nobody_owes">Nobody owes anything.</p>
                @endforelse
            </div>

        </div>

    </section>


    {{-- =====================================================
         LATEST SALES
    ====================================================== --}}

    <section class="dashboard-card glass">

        <div class="dashboard-card-header">
            <h2 data-i18n="latest_sales">Latest sales</h2>
            <a href="{{ route('traders.sales.index') }}" class="dashboard-card-link">
                <span data-i18n="view_all">View all</span>
                <x-icon name="arrow-right" />
            </a>
        </div>

        @forelse ($recentSales as $sale)
            <a href="{{ route('traders.sales.show', $sale) }}" class="draw-list-row">
                <div class="draw-list-main">
                    <strong><x-customer-name :customer="$sale->customer" /></strong>
                    <span>{{ $sale->invoice_number }} · {{ $sale->sold_on->format('d M Y') }}</span>
                </div>
                <strong class="draw-list-amount"><x-rupees :amount="$sale->total_amount" /></strong>
            </a>
        @empty
            <p class="dashboard-empty" data-i18n="no_sales_yet">No sales yet</p>
        @endforelse

    </section>

</div>

@endsection
