@extends('layouts.portal')

@section('title', 'My Home')

@php
    $localNow = now(config('app.business_timezone'));

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
                <span data-i18n="{{ $greetingKey }}">{{ $greeting }}</span>, <span class="dashboard-name">{{ $customer->name }}</span>
            </h1>
            <p class="dashboard-subtitle">
                {{ $customer->customer_code }}@if (filled($customer->remarks)) · {{ $customer->remarks }}@endif
            </p>
        </div>

        <div class="dashboard-actions">
            <a href="{{ route('portal.rice') }}" class="dashboard-action dashboard-action-primary">
                <x-icon name="package" />
                <span data-i18n="order_rice">Order rice</span>
            </a>
            @include('portal.partials.call-office')
            @if ($seats->isNotEmpty())
                <a href="{{ route('portal.statement.pdf') }}" class="dashboard-action">
                    <x-icon name="download" />
                    <span data-i18n="chit_statement">Chit statement</span>
                </a>
            @endif
        </div>


    </section>


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

    @if ($upcomingGroups->isNotEmpty())
        <section class="portal-section">
            <div class="portal-section-head">
                <h2 data-i18n="upcoming_groups">Upcoming Groups</h2>
                <a href="{{ route('portal.upcoming') }}" class="portal-see-all"><span data-i18n="view_all">View all</span> <x-icon name="arrow-right" /></a>
            </div>
            <div class="portal-grid">
                @foreach ($upcomingGroups as $group)
                    @include('portal.partials.upcoming-card', ['joined' => $seats->contains(fn ($seat) => $seat['group']->is($group)), 'joinRequest' => $joinRequests->get($group->id)])
                @endforeach
            </div>
        </section>
    @endif


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

</div>

@endsection
