@extends('layouts.app')

@section('title', ($status === 'pending' ? 'Pending Payouts' : 'Draws').' | SN Chit Funds')

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
            <h1 class="groups-title">
                @if ($status === 'pending')
                    <span data-i18n="pending_payouts">Pending Payouts</span>
                @else
                    <span data-i18n="all_draws">All Draws</span>
                @endif
            </h1>

            <p class="groups-subtitle">
                <span data-i18n="awaiting_payout">Awaiting payout</span>:
                <strong class="draws-pending-total"><x-rupees :amount="$pendingAmount" /></strong>
                ({{ $pendingCount }} <span data-i18n="winners">winners</span>)
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
            '' => ['status_all', 'All'],
            'pending' => ['payout_pending', 'Awaiting payout'],
            'paid' => ['payout_paid', 'Paid out'],
        ] as $tabStatus => [$tabKey, $tabLabel])

            <a
                href="{{ route('draws.index', array_filter(['status' => $tabStatus, 'q' => $search])) }}"
                @class(['status-tab', 'active' => $status === $tabStatus])
                @if ($status === $tabStatus) aria-current="page" @endif
            >
                <span data-i18n="{{ $tabKey }}">{{ $tabLabel }}</span>
                @if ($tabStatus === 'pending')
                    <span class="status-tab-count">{{ $pendingCount }}</span>
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

            <strong data-i18n="no_draws">No draws yet</strong>

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
                        @if ($draw->isPaidOut())
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
