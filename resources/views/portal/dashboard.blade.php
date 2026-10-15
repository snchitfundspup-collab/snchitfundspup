@extends('layouts.portal')

@section('title', 'My Home')

@php
    $localNow = now(config('app.business_timezone'));

    /* SN Chit Funds and SN Traders are paused for customers for now (PORTAL_SECTIONS) */
    $financeOpen = \App\Http\Middleware\EnsurePortalSectionOpen::isOpen('finance');
    $chitOpen = \App\Http\Middleware\EnsurePortalSectionOpen::isOpen('chit');
    $tradersOpen = \App\Http\Middleware\EnsurePortalSectionOpen::isOpen('traders');

    [$greetingKey, $greeting] = match (true) {
        $localNow->hour < 12 => ['greeting_morning', 'Good morning'],
        $localNow->hour < 17 => ['greeting_afternoon', 'Good afternoon'],
        default => ['greeting_evening', 'Good evening'],
    };
@endphp

@section('content')

<div class="dashboard-page portal-page">

    {{-- WELCOME --}}

    <section class="dashboard-welcome glass">

        <div class="dashboard-welcome-glow"></div>

        <div class="dashboard-welcome-text">
            <div class="dashboard-date">
                <x-icon name="calendar" />
                {{ $localNow->format('l, j F Y') }}
            </div>
            <h1 class="dashboard-title">
                <span data-i18n="{{ $greetingKey }}">{{ $greeting }}</span>, <span class="dashboard-name">{{ $family->pluck('name')->join(', ', ' & ') }}</span>
            </h1>
            {{-- everyone sharing this phone, each with their own ID --}}
            @foreach ($family as $person)
                <p class="dashboard-subtitle">
                    @if ($family->count() > 1){{ $person->name }} · @endif{{ $person->customer_code }}@if (filled($person->remarks)) · {{ $person->remarks }}@endif
                </p>
            @endforeach
        </div>

        <div class="dashboard-actions">
            @if ($financeOpen && config('app.portal_loan_plans'))
                <a href="{{ route('portal.loan-plans') }}" class="dashboard-action dashboard-action-primary">
                    <x-icon name="scale" />
                    <span data-i18n="loan_plans">Loan Plans</span>
                </a>
            @endif
            @if ($tradersOpen)
                <a href="{{ route('portal.rice') }}" @class(['dashboard-action', 'dashboard-action-primary' => ! $financeOpen])>
                    <x-icon name="package" />
                    <span data-i18n="order_rice">Order rice</span>
                </a>
            @endif
            @include('portal.partials.call-office')
            @if ($chitOpen && $seats->isNotEmpty())
                <a href="{{ $family->count() > 1 ? route('portal.groups') : route('portal.statement.pdf') }}" class="dashboard-action">
                    <x-icon name="download" />
                    <span data-i18n="chit_statement">Chit statement</span>
                </a>
            @endif
        </div>


    </section>


    {{-- SRI LAKSHMI MICRO FINANCE: running loans, then the loans on offer --}}

    @if ($financeOpen)
        <section class="portal-section">
            <div class="portal-section-head">
                <h2 data-i18n="my_loans">My Loans</h2>
                @if ($loans->isNotEmpty())
                    <a href="{{ route('portal.loans') }}" class="portal-see-all"><span data-i18n="view_all">View all</span> <x-icon name="arrow-right" /></a>
                @endif
            </div>
            @if ($loans->isEmpty())
                @if (config('app.portal_loan_plans'))
                    <p class="portal-empty glass" data-i18n="no_running_loan">You have no running loan. See the loan plans below and call the office to apply.</p>
                @else
                    <p class="portal-empty glass" data-i18n="no_running_loan_call">You have no running loan. Call the office to apply for one.</p>
                @endif
            @else
                <div class="portal-grid">
                    @foreach ($loans as $row)
                        @include('portal.partials.loan-card')
                    @endforeach
                </div>
            @endif
        </section>

        @if (config('app.portal_loan_plans'))
        <section class="portal-section">
            <div class="portal-section-head">
                <h2 data-i18n="loan_plans">Loan Plans</h2>
                <a href="{{ route('portal.loan-plans') }}" class="portal-see-all"><span data-i18n="calculate_word">Calculate</span> <x-icon name="arrow-right" /></a>
            </div>
            <div class="portal-grid">
                @foreach ($loanPlans as $plan)
                    @include('portal.partials.plan-card')
                @endforeach
            </div>
        </section>
        @endif
    @endif


    @if ($chitOpen)

    {{-- MY GROUPS --}}

    <section class="portal-section">
        <div class="portal-section-head">
            <h2 data-i18n="my_groups">My Groups</h2>
            @if ($seats->isNotEmpty())
                <a href="{{ route('portal.groups') }}" class="portal-see-all"><span data-i18n="view_all">View all</span> <x-icon name="arrow-right" /></a>
            @endif
        </div>

        @if ($seats->isEmpty())
            <p class="portal-empty glass" data-i18n="no_groups_yet">You are not in a chit group yet. See the upcoming groups below.</p>
        @else
            <div class="portal-grid">
                @foreach ($seats->take(4) as $seat)
                    @include('portal.partials.seat-card')
                @endforeach
            </div>
        @endif
    </section>


    {{-- UPCOMING GROUPS --}}

    {{-- always shown, so customers know where new groups appear --}}
    <section class="portal-section">
        <div class="portal-section-head">
            <h2 data-i18n="upcoming_groups">Upcoming Groups</h2>
            @if ($upcomingGroups->isNotEmpty())
                <a href="{{ route('portal.upcoming') }}" class="portal-see-all"><span data-i18n="view_all">View all</span> <x-icon name="arrow-right" /></a>
            @endif
        </div>
        @if ($upcomingGroups->isEmpty())
            <p class="portal-empty glass" data-i18n="no_upcoming_groups">No new groups right now. Please check again later.</p>
        @else
            <div class="portal-grid">
                @foreach ($upcomingGroups as $group)
                    @include('portal.partials.upcoming-card', ['joined' => $seats->contains(fn ($seat) => $seat['group']->is($group)), 'joinRequest' => $joinRequests->get($group->id)])
                @endforeach
            </div>
        @endif
    </section>


    @endif


    @if ($tradersOpen)

    {{-- SN TRADERS: rice balance, orders and the rice to buy --}}

    <section class="portal-section">
        <div class="portal-section-head">
            <h2><span class="business-switch-sn">SN</span> Traders</h2>
            <a href="{{ route('portal.rice') }}" class="portal-see-all"><span data-i18n="order_rice">Order rice</span> <x-icon name="arrow-right" /></a>
        </div>

        <div class="portal-traders-summary glass">
            <a href="{{ route('portal.bills') }}" class="portal-traders-fact">
                <small data-i18n="rice_balance">Rice — balance</small>
                @if ($traderBalance > 0.009)
                    <strong class="ledger-total-due"><x-rupees :amount="$traderBalance" /></strong>
                    <span data-i18n="you_owe">you owe</span>
                @elseif ($traderBalance < -0.009)
                    <strong class="ledger-total-paid"><x-rupees :amount="-$traderBalance" /></strong>
                    <span data-i18n="advance_word">advance</span>
                @else
                    <strong class="ledger-total-paid"><x-rupees :amount="0" /></strong>
                    <span data-i18n="settled_word">Settled</span>
                @endif
            </a>
            <a href="{{ route('portal.orders') }}" class="portal-traders-fact">
                <small data-i18n="my_orders">My Orders</small>
                <strong>{{ $openOrders }}</strong>
                <span data-i18n="orders_waiting">waiting for the office</span>
            </a>
            <a href="{{ route('portal.bills') }}" class="portal-traders-fact portal-traders-link">
                <x-icon name="rupee" />
                <span data-i18n="my_bills">Bills &amp; Statement</span>
            </a>
        </div>

        @if ($rice->isNotEmpty())
            <div class="portal-grid">
                @foreach ($rice as $card)
                    @include('portal.partials.rice-card')
                @endforeach
            </div>
        @endif
    </section>

    @endif

</div>

@endsection
