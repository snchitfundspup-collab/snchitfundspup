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
                    Pick a member who still owes, then record a full month or any partial amount.
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

                @if ($results->isEmpty())

                    <p class="members-empty">
                        @if ($search === '')
                            <span data-i18n="nothing_to_collect">Nothing to collect — every member is paid up for this month.</span>
                        @else
                            <span data-i18n="payment_search_none">No members with dues match your search.</span>
                        @endif
                    </p>

                @else

                    <p class="collect-list-count">
                        <strong>{{ $results->total() }}</strong>
                        <span data-i18n="seats_to_collect">to collect</span>
                    </p>

                    @foreach ($results as $seat)

                        @php $status = $seat->collection_status; @endphp

                        <a
                            href="{{ route('payments.create', ['customer' => $seat->customer_id, 'member' => $seat->id]) }}#collect"
                            class="member-result payment-customer-result"
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
                                    · <span data-i18n="month_number">Month</span> {{ $status['month'] }}
                                    · {{ $seat->customer->phone }}
                                </span>

                            </div>

                            <span class="collect-status">

                                <span @class([
                                    'due-badge',
                                    'due-badge-due' => $status['state'] === 'pending',
                                    'due-badge-part' => $status['state'] === 'partial',
                                    'due-badge-month' => $status['state'] === 'due',
                                ])>
                                    <span data-i18n="collect_state_{{ $status['state'] }}">{{ ['pending' => 'Pending', 'partial' => 'Part paid', 'due' => 'Due'][$status['state']] }}</span>
                                </span>

                                <strong class="collect-amount">
                                    <x-rupees :amount="$status['amount_due']" />
                                </strong>

                            </span>

                            <x-icon name="chevron-right" class="payment-result-arrow" />

                        </a>

                    @endforeach


                    <x-pagination :paginator="$results" label="Members to collect pages" />

                @endif

            </div>

        </section>

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


            <h2 class="payment-seats-title">
                <span class="step-number">2</span>
                <span data-i18n="choose_seat">Choose the seat</span>
            </h2>


            <div class="payment-seats">

                @foreach ($customer->memberships as $seat)

                    @php
                        $isRunning = $seat->chitGroup->isRunning();
                        $balanceDue = $seat->balanceDue();
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
                                <span class="due-badge due-badge-due">
                                    <x-rupees :amount="$balanceDue" />
                                    <span data-i18n="due">due</span>
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

        </section>


        @if ($member)

            @include('payments.partials.collect-form', ['member' => $member])

        @endif

    @endif

</div>

@endsection


@push('scripts')
    @vite('resources/js/payments.js')
@endpush
