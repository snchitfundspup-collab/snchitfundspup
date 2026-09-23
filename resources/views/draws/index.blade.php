@extends('layouts.app')

@php
    $titles = [
        '' => ['draw_details_menu', 'Draw Details'],
        'pending' => ['pending_payouts', 'Pending Payouts'],
        'past' => ['past_winners', 'Past Winners'],
        'all' => ['all_draws', 'All Draws'],
    ];
    [$titleKey, $titleText] = $titles[$status];
@endphp

@section('title', $titleText.' | SN Chit Funds')

@push('styles')
    @vite([
        'resources/css/groups.css',
        'resources/css/payments.css',
        'resources/css/draws.css'
    ])
@endpush

@section('content')

<div class="groups-list-page draws-page">


    {{-- =====================================================
         HEADER
    ====================================================== --}}

    <div class="groups-list-header">

        <div>
            @if ($status === '')
                <a
                    href="{{ route('dashboard') }}"
                    class="group-back-link"
                >
                    <x-icon name="arrow-left" />
                    <span data-i18n="back_to_home">Back to Home</span>
                </a>
            @else
                <a
                    href="{{ route('draws.index') }}"
                    class="group-back-link"
                >
                    <x-icon name="arrow-left" />
                    <span data-i18n="back_to_draw_details">Back to Draw Details</span>
                </a>
            @endif

            <h1 class="groups-title">
                <span data-i18n="{{ $titleKey }}">{{ $titleText }}</span>
            </h1>

            <p class="groups-subtitle">
                @if ($status === 'past')
                    <span data-i18n="past_winners_hint">Winners whose payout voucher has been printed.</span>
                @else
                    <span data-i18n="awaiting_payout">Awaiting payout</span>:
                    <strong class="draws-pending-total"><x-rupees :amount="$pendingAmount" /></strong>
                    ({{ $pendingCount }} <span data-i18n="winners">winners</span>)
                @endif
            </p>
        </div>

        <a
            href="{{ route('draws.create') }}"
            class="add-group-button"
        >
            <x-icon name="trophy" />
            <span data-i18n="run_draw">Run Draw</span>
        </a>

    </div>


    {{-- =====================================================
         TABS + SEARCH
    ====================================================== --}}

    <nav class="status-tabs" aria-label="Filter draws">

        @foreach ([
            '' => ['current_draws', 'Current', $currentCount],
            'pending' => ['payout_pending', 'Awaiting payout', $pendingCount],
            'past' => ['past_winners', 'Past Winners', $pastCount],
            'all' => ['status_all', 'All', null],
        ] as $tabStatus => [$tabKey, $tabLabel, $tabCount])

            <a
                href="{{ route('draws.index', array_filter(['status' => $tabStatus, 'q' => $search])) }}"
                @class(['status-tab', 'active' => $status === $tabStatus])
                @if ($status === $tabStatus) aria-current="page" @endif
            >
                <span data-i18n="{{ $tabKey }}">{{ $tabLabel }}</span>
                @if ($tabCount !== null)
                    <span class="status-tab-count">{{ $tabCount }}</span>
                @endif
            </a>

        @endforeach

    </nav>


    <form
        method="GET"
        action="{{ route('draws.index') }}"
        class="payment-filters"
        role="search"
    >

        @if ($status !== '')
            <input type="hidden" name="status" value="{{ $status }}">
        @endif

        <div class="member-search">
            <span class="member-search-icon"><x-icon name="search" /></span>
            <input
                type="search"
                name="q"
                class="member-search-input"
                value="{{ $search }}"
                placeholder="Winner, member ID, group or voucher no."
                data-i18n-placeholder="draw_search_placeholder"
                autocomplete="off"
            >
        </div>

    </form>


    {{-- =====================================================
         LIST
    ====================================================== --}}

    @if ($draws->isEmpty())

        <div class="groups-empty glass">

            <span class="groups-empty-icon icon-3d icon-3d-orange">
                <x-icon name="trophy" />
            </span>

            @if ($status === 'past')
                <strong data-i18n="no_past_winners">No past winners yet</strong>
                <p data-i18n="no_past_winners_text">A draw moves here once its payout voucher is printed.</p>
            @else
                <strong data-i18n="no_draws">No draws yet</strong>
            @endif

            <a
                href="{{ route('draws.create') }}"
                class="add-group-button"
            >
                <x-icon name="trophy" />
                <span data-i18n="run_draw">Run Draw</span>
            </a>

        </div>

    @else

        <div class="payment-list">

            @foreach ($draws as $draw)

                <a
                    href="{{ route('draws.show', $draw) }}"
                    class="payment-row glass"
                >

                    <span class="draw-row-icon icon-3d icon-3d-orange">
                        <x-icon name="trophy" />
                    </span>

                    <div class="payment-row-main">
                        <strong><x-customer-name :customer="$draw->winner->customer" /></strong>
                        <span>
                            {{ $draw->winner->member_code }} · {{ $draw->chitGroup->name }}
                            · <span data-i18n="month_number">Month</span> {{ $draw->month_number }}
                            ({{ $draw->chitGroup->monthPeriodLabel($draw->month_number, withYear: false) }})
                        </span>
                    </div>

                    <div class="payment-row-meta">
                        @if ($draw->isVoucherPrinted())
                            <span class="group-status group-status-completed" data-i18n="voucher_printed">Voucher printed</span>
                            <span>{{ $draw->voucher_number }} · {{ $draw->paid_at->format('d M Y') }}</span>
                        @elseif ($draw->isPaidOut())
                            <span class="group-status group-status-running" data-i18n="payout_paid">Paid out</span>
                            <span>{{ $draw->voucher_number }} · {{ $draw->paid_at->format('d M Y') }}</span>
                        @else
                            <span class="group-status group-status-forming" data-i18n="payout_pending">Awaiting payout</span>
                            <span><span data-i18n="drawn_on">Drawn on</span> {{ $draw->drawn_at->format('d M Y') }}</span>
                        @endif
                    </div>

                    <strong class="payment-row-amount">
                        <x-rupees :amount="$draw->payout_amount ?? $draw->withdrawal_amount" />
                    </strong>

                </a>

            @endforeach

        </div>

        <x-pagination :paginator="$draws" label="Draw pages" />

    @endif

</div>

@endsection
