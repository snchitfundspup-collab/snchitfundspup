@extends('layouts.app')

@section('title', 'Dashboard | Sri Lakshmi Micro Finance')

@push('styles')
    @vite([
        'resources/css/dashboard.css',
        'resources/css/groups.css',
        'resources/css/payments.css',
        'resources/css/traders.css',
        'resources/css/finance.css'
    ])
@endpush

@php
    $localNow = now(config('app.business_timezone'));

    [$greetingKey, $greeting] = match (true) {
        $localNow->hour < 12 => ['greeting_morning', 'Good morning'],
        $localNow->hour < 17 => ['greeting_afternoon', 'Good afternoon'],
        default => ['greeting_evening', 'Good evening'],
    };
@endphp

@section('content')

<div class="dashboard-page finance-page">


    {{-- WELCOME --}}

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

            <p class="dashboard-subtitle" data-i18n="finance_dashboard_subtitle">
                Here's what's happening with Sri Lakshmi Micro Finance today.
            </p>
        </div>

        <div class="dashboard-actions">

            <a href="{{ route('finance.collect') }}" class="dashboard-action dashboard-action-primary">
                <x-icon name="rupee" />
                <span data-i18n="collect_today">Collect</span>
            </a>

            <a href="{{ route('finance.loans.create') }}" class="dashboard-action">
                <x-icon name="plus" />
                <span data-i18n="new_loan">New Loan</span>
            </a>

            @include('components.partials.business-jumps')

        </div>

    </section>


    {{-- MONEY AVAILABLE TO LEND --}}

    <a href="{{ route('finance.capital.index') }}" class="action-alert glass finance-available-alert">
        <span class="stat-icon icon-3d icon-3d-green"><x-icon name="wallet" /></span>
        <span>
            <span data-i18n="available_to_lend">Available to lend</span>
            <strong @class(['ledger-total-due' => $available < 0])><x-rupees :amount="$available" /></strong>
        </span>
        <x-icon name="arrow-right" />
    </a>


    {{-- MONEY CARDS --}}

    <section class="dashboard-stats collection-stats">

        <a href="{{ route('finance.collect', ['tab' => 'overdue']) }}" class="stat-tile glass stat-tile-pending">
            <span class="stat-icon icon-3d icon-3d-red"><x-icon name="info" /></span>
            <span class="stat-body">
                <span class="stat-label" data-i18n="overdue_word">Overdue</span>
                <span class="stat-value stat-value-due"><x-rupees :amount="$overdueTotal" /></span>
                <span class="stat-note">{{ $overdueCount }} <span data-i18n="{{ $overdueCount === 1 ? 'loan_word' : 'loans_word' }}">{{ $overdueCount === 1 ? 'loan' : 'loans' }}</span></span>
            </span>
        </a>

        <a href="{{ route('finance.collect', ['tab' => 'due']) }}" class="stat-tile glass">
            <span class="stat-icon icon-3d icon-3d-orange"><x-icon name="calendar" /></span>
            <span class="stat-body">
                <span class="stat-label" data-i18n="due_today">Due today</span>
                <span class="stat-value finance-due-value"><x-rupees :amount="$dueTodayTotal" /></span>
                <span class="stat-note">{{ $dueTodayCount }} <span data-i18n="{{ $dueTodayCount === 1 ? 'loan_word' : 'loans_word' }}">{{ $dueTodayCount === 1 ? 'loan' : 'loans' }}</span></span>
            </span>
        </a>

        <a href="{{ route('finance.collections.index') }}" class="stat-tile glass">
            <span class="stat-icon icon-3d icon-3d-green"><x-icon name="rupee" /></span>
            <span class="stat-body">
                <span class="stat-label" data-i18n="collected_today">Collected today</span>
                <span class="stat-value"><x-rupees :amount="$collectedToday" /></span>
                <span class="stat-note">{{ $collectedTodayCount }} <span data-i18n="{{ $collectedTodayCount === 1 ? 'receipt_word' : 'receipts_word' }}">{{ $collectedTodayCount === 1 ? 'receipt' : 'receipts' }}</span> · <span data-i18n="this_month">this month</span> <x-rupees :amount="$collectedMonth" /></span>
            </span>
        </a>

        <a href="{{ route('finance.reports.show', ['report' => 'outstanding']) }}" class="stat-tile glass">
            <span class="stat-icon icon-3d icon-3d-blue"><x-icon name="wallet" /></span>
            <span class="stat-body">
                <span class="stat-label" data-i18n="money_outstanding">Money with customers</span>
                <span class="stat-value"><x-rupees :amount="$outstanding" /></span>
                <span class="stat-note">{{ $runningCount }} <span data-i18n="running_loans">running loans</span> · <span data-i18n="lent_this_month">lent this month</span> <x-rupees :amount="$lentMonth" /></span>
            </span>
        </a>

    </section>


    {{-- TO COLLECT + LATEST LOANS --}}

    <section class="dashboard-card glass">

        <div class="dashboard-card-header">
            <h2 data-i18n="to_collect_today">To collect today</h2>
            <a href="{{ route('finance.collect') }}" class="dashboard-card-link">
                <span data-i18n="view_all">View all</span>
                <x-icon name="arrow-right" />
            </a>
        </div>

        <div class="draw-lists">

            <div>
                @forelse ($toCollect as $row)
                    @php [$loan, $standing] = [$row['loan'], $row['standing']]; @endphp
                    <a href="{{ route('finance.loans.show', $loan) }}#collect" class="draw-list-row">
                        <div class="draw-list-main">
                            <strong><x-customer-name :customer="$loan->customer" /></strong>
                            <span>{{ $loan->loan_number }} · {{ $loan->customer->customer_code }} · {{ $loan->customer->phone ?: '—' }}</span>
                        </div>
                        <strong @class(['draw-list-amount', 'stock-negative' => $standing['state'] === 'overdue', 'finance-due-value' => $standing['state'] === 'due'])>
                            <x-rupees :amount="$standing['to_collect']" />
                        </strong>
                    </a>
                @empty
                    <p class="dashboard-empty draw-list-empty" data-i18n="nothing_to_collect">Nothing to collect today.</p>
                @endforelse
            </div>

            <div>
                <h3 class="draw-list-title" data-i18n="latest_loans">Latest loans</h3>
                @forelse ($recentLoans as $loan)
                    <a href="{{ route('finance.loans.show', $loan) }}" class="draw-list-row">
                        <div class="draw-list-main">
                            <strong><x-customer-name :customer="$loan->customer" /></strong>
                            <span>{{ $loan->loan_number }} · {{ $loan->loaned_on->format('d M Y') }} · {{ $loan->frequencyLabel() }}</span>
                        </div>
                        <strong class="draw-list-amount"><x-rupees :amount="$loan->principal" /></strong>
                    </a>
                @empty
                    <p class="dashboard-empty draw-list-empty">
                        <a href="{{ route('finance.loans.create') }}" data-i18n="give_first_loan">Give the first loan</a>
                    </p>
                @endforelse
            </div>

        </div>

    </section>

</div>

@endsection
