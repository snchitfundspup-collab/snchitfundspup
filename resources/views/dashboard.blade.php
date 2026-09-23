@extends('layouts.app')

@section('title', 'Dashboard | SN Chit Funds')

@push('styles')
    @vite('resources/css/dashboard.css')
@endpush

@section('content')

@php
    /* greet and show the date in office time (the app clock is UTC) */
    $localNow = now(config('app.business_timezone'));

    $hour = $localNow->hour;

    [$greetingKey, $greeting] = match (true) {
        $hour < 12 => ['greeting_morning', 'Good morning'],
        $hour < 17 => ['greeting_afternoon', 'Good afternoon'],
        default => ['greeting_evening', 'Good evening'],
    };
@endphp

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

            <p
                class="dashboard-subtitle"
                data-i18n="dashboard_subtitle"
            >
                Here's what's happening with SN Chit Funds today.
            </p>

        </div>


        <div class="dashboard-actions">

            <a
                href="{{ route('payments.create') }}"
                class="dashboard-action dashboard-action-primary"
            >
                <x-icon name="rupee" />
                <span data-i18n="collect_payment">Collect Payment</span>
            </a>

            <a
                href="{{ route('payments.ledger') }}"
                class="dashboard-action"
            >
                <x-icon name="layers" />
                <span data-i18n="payment_ledger">Payment Ledger</span>
            </a>

            <a
                href="{{ route('draws.create') }}"
                class="dashboard-action"
            >
                <x-icon name="trophy" />
                <span data-i18n="run_draw">Run Draw</span>
            </a>

        </div>

    </section>


    {{-- =====================================================
         COLLECTION CARDS
    ====================================================== --}}

    <section class="dashboard-stats collection-stats">

        {{-- today --}}

        <a
            href="{{ route('payments.index', ['date' => $today]) }}"
            class="stat-tile glass"
        >
            <span class="stat-icon icon-3d icon-3d-green">
                <x-icon name="rupee" />
            </span>

            <span class="stat-body">
                <span class="stat-label" data-i18n="collected_today">Collected today</span>
                <span class="stat-value"><x-rupees :amount="$todayCollection" /></span>
                <span class="stat-note">
                    {{ $todayReceipts }} <span data-i18n="receipts">receipts</span>
                </span>
            </span>
        </a>


        {{-- this month --}}

        <a
            href="{{ route('payments.index') }}"
            class="stat-tile glass"
        >
            <span class="stat-icon icon-3d icon-3d-blue">
                <x-icon name="calendar" />
            </span>

            <span class="stat-body">
                <span class="stat-label">
                    <span data-i18n="collected_this_month">Collected this month</span>
                    · {{ $localNow->format('M Y') }}
                </span>
                <span class="stat-value"><x-rupees :amount="$monthCollection" /></span>
                <span class="stat-note">
                    {{ $monthReceipts }} <span data-i18n="receipts">receipts</span>
                </span>
            </span>
        </a>


        {{-- pending --}}

        <a
            href="{{ route('payments.create') }}"
            class="stat-tile glass stat-tile-pending"
        >
            <span class="stat-icon icon-3d icon-3d-red">
                <x-icon name="info" />
            </span>

            <span class="stat-body">
                <span class="stat-label" data-i18n="pending_collections">Pending collections</span>
                <span class="stat-value stat-value-due"><x-rupees :amount="$pending['amount']" /></span>
                <span class="stat-note">
                    {{ $pending['members'] }} <span data-i18n="members_owe">members to collect from</span>
                    @if ($pending['overdue'] > 0)
                        · <x-rupees :amount="$pending['overdue']" /> <span data-i18n="overdue_earlier">overdue from earlier months</span>
                    @endif
                </span>
            </span>
        </a>

    </section>


    {{-- =====================================================
         DRAW DETAILS
    ====================================================== --}}

    <section class="dashboard-card glass">

        <div class="dashboard-card-header">

            <h2 data-i18n="draw_details_menu">Draw Details</h2>

            <a
                href="{{ route('draws.index') }}"
                class="dashboard-card-link"
            >
                <span data-i18n="view_all">View all</span>
                <x-icon name="arrow-right" />
            </a>

        </div>


        <div class="dashboard-stats draw-stats">

            <a
                href="{{ route('draws.create') }}"
                class="stat-tile draw-stat"
            >
                <span class="stat-icon icon-3d icon-3d-orange">
                    <x-icon name="trophy" />
                </span>
                <span class="stat-body">
                    <span class="stat-label" data-i18n="draws_due_now">Draws due now</span>
                    <span class="stat-value">{{ $drawsDue->count() }}</span>
                    <span class="stat-note" data-i18n="draws_due_note">Groups ready for this month's draw</span>
                </span>
            </a>

            <a
                href="{{ route('draws.index', ['status' => 'pending']) }}"
                class="stat-tile draw-stat"
            >
                <span class="stat-icon icon-3d icon-3d-red">
                    <x-icon name="rupee" />
                </span>
                <span class="stat-body">
                    <span class="stat-label" data-i18n="awaiting_payout">Awaiting payout</span>
                    <span class="stat-value stat-value-due"><x-rupees :amount="$pendingPayouts['amount']" /></span>
                    <span class="stat-note">
                        {{ $pendingPayouts['count'] }} <span data-i18n="winners">winners</span>
                    </span>
                </span>
            </a>

            <a
                href="{{ route('draws.winners') }}"
                class="stat-tile draw-stat"
            >
                <span class="stat-icon icon-3d icon-3d-green">
                    <x-icon name="check" />
                </span>
                <span class="stat-body">
                    <span class="stat-label">
                        <span data-i18n="prizes_paid_this_month">Prizes paid this month</span>
                    </span>
                    <span class="stat-value"><x-rupees :amount="$paidThisMonth['amount']" /></span>
                    <span class="stat-note">
                        {{ $paidThisMonth['count'] }} <span data-i18n="winners">winners</span>
                    </span>
                </span>
            </a>

        </div>


        <div class="draw-lists">

            {{-- groups whose draw can be run now --}}

            <div>

                <h3 class="draw-list-title" data-i18n="draws_due_now">Draws due now</h3>

                @forelse ($drawsDue as $due)

                    <div class="draw-list-row">

                        <div class="draw-list-main">
                            <strong>{{ $due['group']->name }}</strong>
                            <span>
                                <span data-i18n="month_number">Month</span> {{ $due['month'] }}
                                · {{ $due['group']->monthPeriodLabel($due['month'], withYear: false) }}
                            </span>
                        </div>

                        <strong class="draw-list-amount"><x-rupees :amount="$due['prize']" /></strong>

                        <a
                            href="{{ route('draws.create', ['group' => $due['group']->id]) }}"
                            class="draw-list-button"
                        >
                            <x-icon name="trophy" />
                            <span data-i18n="run_word">Run</span>
                        </a>

                    </div>

                @empty

                    <p class="dashboard-empty draw-list-empty" data-i18n="no_draws_due">No draws due right now.</p>

                @endforelse

            </div>


            {{-- latest winners --}}

            <div>

                <h3 class="draw-list-title" data-i18n="recent_winners">Recent winners</h3>

                @forelse ($recentDraws as $draw)

                    <a
                        href="{{ route('draws.show', $draw) }}"
                        class="draw-list-row"
                    >

                        <div class="draw-list-main">
                            <strong><x-customer-name :customer="$draw->winner->customer" /></strong>
                            <span>
                                {{ $draw->winner->member_code }} · {{ $draw->chitGroup->name }}
                                · M{{ $draw->month_number }}
                            </span>
                        </div>

                        <div class="draw-list-side">
                            <strong class="draw-list-amount"><x-rupees :amount="$draw->prizeAmount()" /></strong>
                            @if ($draw->isPaidOut())
                                <span class="group-status group-status-running" data-i18n="payout_paid">Paid out</span>
                            @else
                                <span class="group-status group-status-forming" data-i18n="payout_pending">Awaiting payout</span>
                            @endif
                        </div>

                    </a>

                @empty

                    <p class="dashboard-empty draw-list-empty" data-i18n="no_draws">No draws yet</p>

                @endforelse

            </div>

        </div>

    </section>


    {{-- =====================================================
         GROUP DETAILS
    ====================================================== --}}

    <section class="dashboard-card glass">

        <div class="dashboard-card-header">

            <h2 data-i18n="group_details">Group details</h2>

            <a
                href="{{ route('groups.index') }}"
                class="dashboard-card-link"
            >
                <span data-i18n="view_all">View all</span>
                <x-icon name="arrow-right" />
            </a>

        </div>


        @if (count($groupCards) === 0)

            <p
                class="dashboard-empty"
                data-i18n="groups_empty_title"
            >
                No groups yet
            </p>

        @else

            <div class="dashboard-groups">

                @foreach ($groupCards as $card)

                    @php $group = $card['group']; @endphp

                    <a
                        href="{{ route('groups.show', $group) }}"
                        class="dashboard-group"
                    >

                        <div class="dashboard-group-top">

                            <span @class([
                                'dashboard-group-icon',
                                'icon-3d',
                                'icon-3d-orange' => $group->type === 'draw',
                                'icon-3d-purple' => $group->type === 'auction',
                            ])>
                                <x-icon :name="$group->type === 'auction' ? 'rupee' : 'trophy'" />
                            </span>

                            <div class="dashboard-group-name">
                                <strong>{{ $group->name }}</strong>
                                <span>
                                    <x-rupees :amount="$group->amount" />
                                    · <x-rupees :amount="$group->installment_amount" />/<span data-i18n="month_word">month</span>
                                </span>
                            </div>

                            <x-group-status :status="$group->status" />

                        </div>


                        <dl class="dashboard-group-facts">

                            <div>
                                <dt data-i18n="group_members">Members</dt>
                                <dd>{{ $card['members'] }} / {{ $group->member_count }}</dd>
                            </div>

                            @if ($group->isRunning())

                                <div>
                                    <dt data-i18n="current_month">Current month</dt>
                                    <dd>{{ $card['current_month'] }} / {{ $group->months }}</dd>
                                </div>

                                <div>
                                    <dt data-i18n="due_now">Due now</dt>
                                    <dd @class(['is-due' => $card['due_now'] > 0])><x-rupees :amount="$card['due_now']" /></dd>
                                </div>

                                @if ($group->type === 'draw')
                                    <div>
                                        <dt data-i18n="draws_held">Draws held</dt>
                                        <dd>{{ $group->draws_count }} / {{ $group->months }}</dd>
                                    </div>
                                @endif

                            @else

                                <div>
                                    <dt data-i18n="start_date">Start Date</dt>
                                    <dd>{{ $group->start_date->format('d M Y') }}</dd>
                                </div>

                            @endif

                        </dl>


                        @if ($group->isRunning())

                            <div class="dashboard-group-progress">

                                <div class="progress-labels">
                                    <span>
                                        <span data-i18n="month_number">Month</span> {{ $card['current_month'] }}
                                        <span data-i18n="collection">collection</span>
                                        @if ($card['current_month'] > 0)
                                            · {{ $group->monthPeriodLabel($card['current_month'], withYear: false) }}
                                        @endif
                                    </span>
                                    <strong>
                                        <x-rupees :amount="$card['collected']" />
                                        / <x-rupees :amount="$card['expected']" />
                                    </strong>
                                </div>

                                <div
                                    class="progress-track"
                                    role="progressbar"
                                    aria-valuenow="{{ $card['percent'] }}"
                                    aria-valuemin="0"
                                    aria-valuemax="100"
                                    aria-label="Month {{ $card['current_month'] }} collection {{ $card['percent'] }}%"
                                >
                                    <div
                                        class="progress-bar"
                                        style="width: {{ $card['percent'] }}%"
                                    ></div>
                                </div>

                            </div>

                        @endif

                    </a>

                @endforeach

            </div>

        @endif

    </section>

</div>

@endsection
