@extends('layouts.app')

@section('title', $group->name.' | SN Chit Funds')

@push('styles')
    @vite('resources/css/groups.css')
@endpush

@section('content')

@php
    /* in a running group, highlight the group month today falls in
       (months run from the start date: 15 Sep – 14 Oct is month 1) */
    $currentMonthNumber = $group->isRunning() ? $group->currentMonthNumber() : null;

    $totalWithdrawals = $group->payouts->sum('withdrawal_amount');

    $memberTotal = $group->members->count();
@endphp

<div class="group-show-page">


    {{-- =====================================================
         FLASH MESSAGES
    ====================================================== --}}

    @if (session('success'))
        <div class="group-flash group-flash-success" role="status">
            <x-icon name="check" />
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="group-flash group-flash-error" role="alert">
            <x-icon name="info" />
            {{ session('error') }}
        </div>
    @endif


    {{-- =====================================================
         HEADER
    ====================================================== --}}

    <section class="group-hero glass">

        <a
            href="{{ route('groups.index') }}"
            class="group-back-link"
        >
            <x-icon name="arrow-left" />
            <span data-i18n="back_to_groups">Back to Groups</span>
        </a>


        <div class="group-hero-main">

            <span @class([
                'group-hero-icon',
                'icon-3d',
                'icon-3d-orange' => $group->type === 'draw',
                'icon-3d-purple' => $group->type === 'auction',
            ])>
                <x-icon :name="$group->type === 'auction' ? 'rupee' : 'trophy'" />
            </span>

            <div class="group-hero-text">

                <div class="group-hero-tags">

                    <span
                        class="group-type-label"
                        data-i18n="type_{{ $group->type }}"
                    >
                        {{ ucfirst($group->type) }}
                    </span>

                    <x-group-status :status="$group->status" />

                </div>

                <h1 class="group-hero-title">
                    {{ $group->name }}
                </h1>

                <p class="group-hero-meta">
                    {{ $group->start_date->format('M Y') }}
                    →
                    {{ $group->endDate()->format('M Y') }}

                    @if ($group->started_at)
                        ·
                        <span data-i18n="started_on">Started on</span>
                        {{ $group->started_at->format('d M Y') }}
                    @endif
                </p>

            </div>


            <div class="group-hero-actions">

                <a
                    href="{{ route('groups.edit', $group) }}"
                    class="group-action"
                >
                    <x-icon name="edit" />
                    <span data-i18n="edit_group">Edit Group</span>
                </a>

                <a
                    href="{{ route('groups.members.edit', $group) }}"
                    class="group-action"
                >
                    <x-icon name="users" />
                    <span data-i18n="edit_members">Edit Members</span>
                </a>

                @unless ($group->isForming())
                    <a
                        href="{{ route('payments.ledger', ['group' => $group->id]) }}"
                        class="group-action"
                    >
                        <x-icon name="layers" />
                        <span data-i18n="payment_ledger">Payment Ledger</span>
                    </a>
                @endunless

                @if ($group->isForming())

                    <form
                        method="POST"
                        action="{{ route('groups.start', $group) }}"
                        onsubmit="return confirm('Start {{ addslashes($group->name) }} now?')"
                    >
                        @csrf

                        <button
                            type="submit"
                            class="group-action group-action-start"
                        >
                            <x-icon name="arrow-right" />
                            <span data-i18n="start_group">Start Group</span>
                        </button>
                    </form>

                @endif

            </div>

        </div>

    </section>


    {{-- =====================================================
         KEY FACTS
    ====================================================== --}}

    <section class="group-facts">

        @foreach ([
            ['rupee', 'orange', 'chit_amount', 'Chit Amount', $group->amount, true],
            ['calendar', 'blue', 'monthly_installment', 'Monthly Installment', $group->monthlyInstallment(), true],
            ['layers', 'purple', 'group_months', 'Months', $group->months, false],
            ['users', 'green', 'group_members', 'Members', $memberTotal.' / '.$group->member_count, false],
            ['chart', 'blue', 'commission', 'Commission / month', $group->commission_amount, true],
        ] as [$factIcon, $factTone, $factKey, $factLabel, $factValue, $isMoney])

            <div class="group-fact glass">

                <span class="group-fact-icon icon-3d icon-3d-{{ $factTone }}">
                    <x-icon :name="$factIcon" />
                </span>

                <span class="group-fact-body">

                    <span class="group-fact-value">
                        @if ($isMoney)
                            <x-rupees :amount="$factValue" />
                        @else
                            {{ $factValue }}
                        @endif
                    </span>

                    <span
                        class="group-fact-label"
                        data-i18n="{{ $factKey }}"
                    >
                        {{ $factLabel }}
                    </span>

                </span>

            </div>

        @endforeach

    </section>


    <div class="group-show-stack">


        @include('groups.partials.members-panel')


        {{-- =================================================
             WITHDRAWAL SCHEDULE (collapsed — full editing is in
             Edit Group; kept here so running groups can still see it)
        ================================================== --}}

        <details class="group-panel schedule-collapse glass">

            <summary class="schedule-summary">

                <span class="schedule-summary-title">
                    <span data-i18n="withdrawal_schedule">Withdrawal schedule</span>
                    <span class="schedule-summary-months">({{ $group->months }})</span>
                </span>

                <span class="group-panel-note">
                    <span data-i18n="total">Total</span>:
                    <x-rupees :amount="$totalWithdrawals" />
                </span>

                <x-icon name="chevron-down" class="schedule-summary-caret" />

            </summary>


            <div class="schedule-table-wrapper">

                <table class="schedule-table schedule-table-readonly">

                    <thead>
                        <tr>
                            <th data-i18n="month_number">Month</th>
                            <th data-i18n="month_date">Period</th>
                            <th
                                class="text-right"
                                data-i18n="withdrawal_amount"
                            >Withdrawal Amount (₹)</th>
                        </tr>
                    </thead>

                    <tbody>

                        @foreach ($group->payouts as $payout)

                            <tr @class(['is-current' => $payout->month_number === $currentMonthNumber])>

                                <td class="schedule-month">
                                    {{ $payout->month_number }}
                                </td>

                                <td class="schedule-date">
                                    {{ $group->monthPeriodLabel($payout->month_number) }}

                                    @if ($payout->month_number === $currentMonthNumber)
                                        <span
                                            class="schedule-now"
                                            data-i18n="this_month"
                                        >This month</span>
                                    @endif
                                </td>

                                <td class="text-right schedule-amount">
                                    <x-rupees :amount="$payout->withdrawal_amount" />
                                </td>

                            </tr>

                        @endforeach

                    </tbody>

                </table>

            </div>

        </details>



    </div>

</div>


{{-- =========================================================
     MEMBER DETAILS POPUP (filled by group-show.js)
========================================================= --}}

<div
    class="member-modal"
    id="memberModal"
    hidden
>

    <div
        class="member-modal-overlay"
        data-close-member-modal
    ></div>

    <div
        class="member-modal-dialog"
        role="dialog"
        aria-modal="true"
        aria-labelledby="memberModalName"
    >

        <div class="member-modal-header">

            <span
                class="member-modal-avatar avatar-3d icon-3d icon-3d-orange"
                id="memberModalAvatar"
                aria-hidden="true"
            ></span>

            <div class="member-modal-heading">
                <span
                    class="member-modal-code"
                    id="memberModalCode"
                ></span>
                <h2 id="memberModalName"></h2>
            </div>

            <button
                type="button"
                class="member-modal-close"
                data-close-member-modal
                aria-label="Close"
            >
                <x-icon name="x" />
            </button>

        </div>


        <div class="member-modal-body">

            <dl class="member-detail-grid">

                <div>
                    <dt data-i18n="customer_id">Customer ID</dt>
                    <dd id="memberDetailCustomerCode"></dd>
                </div>

                <div>
                    <dt data-i18n="member_id_here">Member ID in this group</dt>
                    <dd id="memberDetailMemberCode"></dd>
                </div>

                <div>
                    <dt data-i18n="phone">Phone</dt>
                    <dd class="member-detail-phone">
                        <span id="memberDetailPhone"></span>

                        <a
                            id="memberDetailCall"
                            class="member-call-button"
                            href="#"
                        >
                            <x-icon name="phone" />
                            <span data-i18n="call">Call</span>
                        </a>
                    </dd>
                </div>

                <div>
                    <dt data-i18n="emailLabel">Email</dt>
                    <dd id="memberDetailEmail"></dd>
                </div>

                <div class="member-detail-full">
                    <dt data-i18n="identification">Identification</dt>
                    <dd id="memberDetailRemarks"></dd>
                </div>

                <div class="member-detail-full">
                    <dt data-i18n="addressLabel">Address</dt>
                    <dd id="memberDetailAddress"></dd>
                </div>

                <div>
                    <dt data-i18n="status">Status</dt>
                    <dd id="memberDetailStatus"></dd>
                </div>

            </dl>


            <h3
                class="member-groups-title"
                data-i18n="linked_groups"
            >
                Groups
            </h3>

            <ul
                class="member-groups"
                id="memberDetailGroups"
            ></ul>

        </div>

    </div>

</div>


<script type="application/json" id="membersData">@json($membersData)</script>

@endsection


@push('scripts')
    @vite('resources/js/group-show.js')
@endpush
