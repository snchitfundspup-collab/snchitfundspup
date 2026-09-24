@extends('layouts.app')

@section('title', 'Collect Payment | SN Chit Funds')

@push('styles')
    @vite([
        'resources/css/create.css',
        'resources/css/groups.css',
        'resources/css/payments.css'
    ])
@endpush

@section('content')

<div class="group-show-page payments-page">


    {{-- =====================================================
         FLASH MESSAGES
    ====================================================== --}}

    @if (session('success'))
        <div class="group-flash group-flash-success" role="status">
            <x-icon name="check" />
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="group-flash group-flash-error" role="alert">
            <x-icon name="info" />
            {{ $errors->first() }}
        </div>
    @endif


    {{-- =====================================================
         HEADER
    ====================================================== --}}

    <section class="group-hero glass">

        <div class="group-hero-main">

            <span class="group-hero-icon icon-3d icon-3d-green">
                <x-icon name="rupee" />
            </span>

            <div class="group-hero-text">

                <h1 class="group-hero-title">
                    <span data-i18n="collect_payment">Collect Payment</span>
                </h1>

                <p
                    class="group-hero-meta"
                    data-i18n="collect_payment_subtitle"
                >
                    Pick a member, choose the month and record the payment.
                </p>

            </div>

            <a
                href="{{ route('payments.index') }}"
                class="group-action"
            >
                <x-icon name="chart" />
                <span data-i18n="all_payments">All Payments</span>
            </a>

        </div>

    </section>


    @if (! $customer)

        {{-- =================================================
             STEP 1: FIND THE CUSTOMER
        ================================================== --}}

        <section class="group-panel glass payment-step">

            <div class="group-panel-header">
                <h2>
                    <span class="step-number">1</span>
                    <span data-i18n="find_customer">Members to collect from</span>
                </h2>
            </div>


            <form
                method="GET"
                action="{{ route('payments.create') }}"
                class="member-search"
                id="paymentSearchForm"
                role="search"
            >

                <span class="member-search-icon">
                    <x-icon name="search" />
                </span>

                <input
                    type="search"
                    name="q"
                    id="paymentSearchInput"
                    class="member-search-input"
                    value="{{ $search }}"
                    placeholder="Search by name, ID, phone, identification or group"
                    data-i18n-placeholder="payment_search_placeholder"
                    autocomplete="off"
                    autofocus
                >

            </form>


            <div
                class="member-search-results"
                id="paymentSearchResults"
            >

                @if ($search === '')

                    {{-- Pending: past the due date · Due: 1st of the month until the due date --}}

                    <nav class="status-tabs collect-tabs" aria-label="Members to collect">

                        @foreach ([
                            'pending' => ['collect_state_pending', 'Pending'],
                            'due' => ['collect_state_due', 'Due'],
                        ] as $tabName => [$tabKey, $tabLabel])

                            <a
                                href="{{ route('payments.create', $tabName === 'due' ? ['tab' => 'due'] : []) }}"
                                @class(['status-tab', 'collect-tab-'.$tabName, 'active' => $tab === $tabName])
                                @if ($tab === $tabName) aria-current="page" @endif
                            >
                                <span data-i18n="{{ $tabKey }}">{{ $tabLabel }}</span>
                                <span class="status-tab-count">{{ $tabCounts[$tabName] }}</span>
                            </a>

                        @endforeach

                    </nav>

                @endif

                @if ($results->isEmpty())

                    <p class="members-empty">
                        @if ($search !== '')
                            <span data-i18n="payment_search_none_any">No members match your search.</span>
                        @elseif ($tab === 'due')
                            <span data-i18n="nothing_due">No members are due right now. Search to find anyone else.</span>
                        @else
                            <span data-i18n="nothing_pending">No pending members right now. Search to find anyone else.</span>
                        @endif
                    </p>

                @else

                    <p class="collect-list-count">
                        <strong>{{ $results->total() }}</strong>
                        @if ($search !== '')
                            <span data-i18n="seats_found">found</span>
                        @elseif ($tab === 'due')
                            <span data-i18n="seats_due">due — search to find anyone else</span>
                        @else
                            <span data-i18n="seats_pending">pending — search to find anyone else</span>
                        @endif
                    </p>

                    @foreach ($results as $seat)

                        @php $status = $seat->collection_status; @endphp

                        <div class="member-result payment-customer-result collect-row">

                        <a
                            href="{{ route('payments.create', ['customer' => $seat->customer_id, 'member' => $seat->id]) }}#collect"
                            class="collect-row-link"
                        >

                            <x-customer-avatar :customer="$seat->customer" />

                            <div class="member-card-info">

                                <span class="member-card-code">
                                    {{ $seat->member_code }}
                                </span>

                                <strong class="member-card-name">
                                    <x-customer-name :customer="$seat->customer" />
                                </strong>

                                <span class="member-card-identification">
                                    <span class="collect-group-name">{{ $seat->chitGroup->name }}</span>
                                    @if (count($status['months']) > 1)
                                        · <span data-i18n="months_word">Months</span> {{ implode(', ', $status['months']) }}
                                    @elseif ($status['month'] > 0)
                                        · <span data-i18n="month_number">Month</span> {{ $status['month'] }}
                                    @endif
                                    · {{ $seat->customer->phone }}
                                </span>

                            </div>

                            <span class="collect-status">

                                <span @class([
                                    'due-badge',
                                    'due-badge-due' => $status['state'] === 'pending',
                                    'due-badge-part' => $status['state'] === 'partial',
                                    'due-badge-month' => $status['state'] === 'due',
                                    'due-badge-upcoming' => $status['state'] === 'upcoming',
                                    'due-badge-ok' => $status['state'] === 'clear',
                                ])>
                                    <span data-i18n="collect_state_{{ $status['state'] }}">{{ ['pending' => 'Pending', 'partial' => 'Part paid', 'due' => 'Due', 'upcoming' => 'Upcoming', 'clear' => 'Paid up'][$status['state']] }}</span>
                                </span>

                                <strong class="collect-amount">
                                    <x-rupees :amount="$status['state'] === 'upcoming' ? $status['next_balance'] : $status['amount_due']" />
                                </strong>

                            </span>

                            <x-icon name="chevron-right" class="payment-result-arrow" />

                        </a>

                        @if ($status['state'] !== 'clear')
                            <button
                                type="button"
                                class="quick-collect-button"
                                data-quick-collect
                                data-member="{{ $seat->id }}"
                                data-name="{{ $seat->customer->name }}{{ filled($seat->customer->remarks) ? ' ('.$seat->customer->remarks.')' : '' }}"
                                data-code="{{ $seat->member_code }}"
                                data-group="{{ $seat->chitGroup->name }}"
                                data-full-form="{{ route('payments.create', ['customer' => $seat->customer_id, 'member' => $seat->id]) }}#collect"
                                data-months="{{ json_encode($seat->collectableMonths()) }}"
                                aria-label="Collect from {{ $seat->customer->name }}"
                                title="Collect"
                            >
                                <x-icon name="rupee" />
                            </button>
                        @endif

                        @if ($seat->customer->phone)
                            <a
                                href="tel:{{ preg_replace('/[^\d+]/', '', $seat->customer->phone) }}"
                                class="member-call-button collect-call-button"
                                aria-label="Call {{ $seat->customer->name }} on {{ $seat->customer->phone }}"
                                title="Call {{ $seat->customer->phone }}"
                            >
                                <x-icon name="phone" />
                                <span data-i18n="call">Call</span>
                            </a>
                        @endif

                        </div>

                    @endforeach


                    <x-pagination :paginator="$results" label="Members to collect pages" />

                @endif

            </div>

        </section>

        @include('payments.partials.quick-collect-modal')

    @else

        {{-- =================================================
             STEP 2: CUSTOMER + SEATS
        ================================================== --}}

        <section class="group-panel glass payment-step">

            <div class="payment-customer">

                <x-customer-avatar :customer="$customer" class="payment-customer-avatar" />

                <div class="member-card-info">

                    <span class="member-card-code">{{ $customer->customer_code }}</span>

                    <strong class="payment-customer-name">{{ $customer->name }}</strong>

                    <span class="member-card-identification">
                        {{ $customer->remarks ?: '—' }} · {{ $customer->phone }}
                    </span>

                </div>

                <div class="member-card-actions">

                    @if ($customer->phone)
                        <a
                            href="tel:{{ preg_replace('/[^\d+]/', '', $customer->phone) }}"
                            class="member-call-button"
                            aria-label="Call {{ $customer->name }}"
                        >
                            <x-icon name="phone" />
                            <span data-i18n="call">Call</span>
                        </a>
                    @endif

                    <a
                        href="{{ route('payments.create') }}"
                        class="group-action payment-change-customer"
                    >
                        <x-icon name="search" />
                        <span data-i18n="change_customer">Change</span>
                    </a>

                </div>

            </div>


            @php
                /* one seat → no seat step: the form opens straight away */
                $showSeats = $customer->memberships->count() > 1;
            @endphp

            @unless ($showSeats)
                @php $onlySeat = $customer->memberships->first(); @endphp
                @if ($onlySeat)
                    <p class="payment-single-seat">
                        <span class="member-card-code">{{ $onlySeat->member_code }}</span>
                        <strong>{{ $onlySeat->chitGroup->name }}</strong>
                        · <x-rupees :amount="$onlySeat->chitGroup->installment_amount" />/<span data-i18n="month_word">month</span>
                        @unless ($onlySeat->chitGroup->isRunning())
                            · <x-group-status :status="$onlySeat->chitGroup->status" />
                        @endunless
                    </p>
                @endif
            @endunless

            @if ($showSeats)

            <h2 class="payment-seats-title">
                <span class="step-number">2</span>
                <span data-i18n="choose_seat">Choose the seat</span>
            </h2>


            <div class="payment-seats">

                @foreach ($customer->memberships as $seat)

                    @php
                        $isRunning = $seat->chitGroup->isRunning();
                        $balanceDue = $seat->balanceDue();
                        $seatState = $isRunning ? $seat->collectionStatus()['state'] : null;
                        $nextMonth = $seat->nextUnpaidMonth();
                    @endphp

                    <div @class([
                        'payment-seat',
                        'is-selected' => $member?->id === $seat->id,
                        'is-disabled' => ! $isRunning,
                    ])>

                        <div class="payment-seat-main">

                            <span class="member-card-code">{{ $seat->member_code }}</span>

                            <strong>{{ $seat->chitGroup->name }}</strong>

                            <span class="member-card-identification">
                                <x-rupees :amount="$seat->chitGroup->installment_amount" /> / <span data-i18n="month_word">month</span>
                            </span>

                        </div>


                        <div class="payment-seat-status">

                            @if (! $isRunning)
                                <x-group-status :status="$seat->chitGroup->status" />
                            @elseif ($balanceDue > 0)
                                <span @class([
                                    'due-badge',
                                    'due-badge-due' => $seatState === 'pending',
                                    'due-badge-month' => in_array($seatState, ['due', 'partial'], true),
                                    'due-badge-upcoming' => $seatState === 'upcoming',
                                ])>
                                    <x-rupees :amount="$balanceDue" />
                                    @if ($seatState === 'pending')
                                        <span data-i18n="pending_word">pending</span>
                                    @elseif ($seatState === 'upcoming')
                                        <span data-i18n="upcoming_word">upcoming</span>
                                    @else
                                        <span data-i18n="due">due</span>
                                    @endif
                                </span>
                            @elseif ($nextMonth === null)
                                <span class="due-badge due-badge-done" data-i18n="fully_paid">Fully paid</span>
                            @else
                                <span class="due-badge due-badge-ok" data-i18n="paid_up">Paid up</span>
                            @endif

                            @if ($isRunning && $nextMonth)
                                <span class="payment-seat-next">
                                    <span data-i18n="next_month">Next</span>:
                                    <span data-i18n="month_number">Month</span> {{ $nextMonth }}
                                </span>
                            @endif

                        </div>


                        @if ($isRunning && $nextMonth)
                            <a
                                href="{{ route('payments.create', ['customer' => $customer->id, 'member' => $seat->id]) }}#collect"
                                class="member-add-button payment-collect-button"
                            >
                                <x-icon name="rupee" />
                                <span data-i18n="collect">Collect</span>
                            </a>
                        @endif

                    </div>

                @endforeach

            </div>

            @endif

        </section>


        @if ($member)

            @include('payments.partials.collect-form', ['member' => $member, 'stepNumber' => $showSeats ? 3 : 2])

        @endif

    @endif

</div>

@endsection


@push('scripts')
    @vite('resources/js/payments.js')
@endpush
