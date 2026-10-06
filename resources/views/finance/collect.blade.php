@extends('layouts.app')

@section('title', 'Collect | Sri Lakshmi Micro Finance')

@push('styles')
    @vite([
        'resources/css/groups.css',
        'resources/css/payments.css',
        'resources/css/traders.css',
        'resources/css/finance.css'
    ])
@endpush

@section('content')

<div class="groups-list-page payments-page traders-page finance-page">

    @include('traders.partials.flash')

    <div class="groups-list-header">
        <div>
            <h1 class="groups-title" data-i18n="collect_today">Collect</h1>
            <p class="groups-subtitle" data-i18n="collect_subtitle">Overdue loans first (red), then today's instalments (orange). Tap a loan to record the money.</p>
        </div>
        <div class="ledger-actions">
            <a href="{{ route('finance.collections.index') }}" class="group-action">
                <x-icon name="chart" />
                <span data-i18n="all_collections">All Collections</span>
            </a>
        </div>
    </div>


    <section class="payment-summary-bar glass dues-summary">
        <div class="payment-summary-main">
            <span class="payment-summary-range" data-i18n="overdue_word">Overdue</span>
            <strong class="payment-summary-total dues-total-pending"><x-rupees :amount="$totals['overdue']" /></strong>
        </div>
        <div class="payment-summary-main">
            <span class="payment-summary-range" data-i18n="due_today">Due today</span>
            <strong class="payment-summary-total finance-due-value"><x-rupees :amount="$totals['due']" /></strong>
        </div>
    </section>


    <nav class="status-tabs" aria-label="Collect">
        @foreach (['overdue' => ['overdue_word', 'Overdue'], 'due' => ['due_today', 'Due today'], 'all' => ['all_running', 'All running loans']] as $tabName => [$tabKey, $tabLabel])
            <a
                href="{{ route('finance.collect', array_filter(['tab' => $tabName, 'q' => $search])) }}"
                @class(['status-tab', 'active' => $tab === $tabName, 'status-tab-pending' => $tabName === 'overdue'])
                @if ($tab === $tabName) aria-current="page" @endif
            >
                <span data-i18n="{{ $tabKey }}">{{ $tabLabel }}</span>
                <span class="status-tab-count">{{ $counts[$tabName] }}</span>
            </a>
        @endforeach
    </nav>


    <form method="GET" action="{{ route('finance.collect') }}" class="payment-filters" role="search">
        <div class="member-search payment-filter-search">
            <span class="member-search-icon"><x-icon name="search" /></span>
            <input type="search" name="q" class="member-search-input" value="{{ $search }}" placeholder="Search name, ID, phone or loan number" autocomplete="off">
        </div>
    </form>


    <section class="group-panel glass payment-step">

        @if ($rows->isEmpty())

            <p class="members-empty">
                @if ($search !== '')
                    <span data-i18n="no_loans_match">No running loans match your search.</span>
                @elseif ($tab === 'overdue')
                    <span data-i18n="nothing_overdue">Nothing is overdue.</span>
                @else
                    <span data-i18n="nothing_to_collect">Nothing to collect today.</span>
                @endif
            </p>

        @else

            @foreach ($rows as $row)
                @php [$loan, $standing] = [$row['loan'], $row['standing']]; @endphp

                <div class="member-result payment-customer-result collect-row">

                    <a href="{{ route('finance.loans.show', $loan) }}#collect" class="collect-row-link">

                        <x-customer-avatar :customer="$loan->customer" />

                        <div class="member-card-info">
                            <span class="member-card-code">{{ $loan->loan_number }} · {{ $loan->customer->customer_code }}</span>
                            <strong class="member-card-name"><x-customer-name :customer="$loan->customer" /></strong>
                            <span class="member-card-identification">
                                {{ $loan->frequencyLabel() }} <x-rupees :amount="$loan->installment_amount" />
                                · <span data-i18n="balance_word">Balance</span> <x-rupees :amount="$loan->balance()" />
                                @if ($standing['days_overdue'] > 0)
                                    · {{ $standing['days_overdue'] }} <span data-i18n="days_late">days late</span>
                                @endif
                            </span>
                        </div>

                        <span class="collect-status">
                            @include('finance.partials.state-badge', ['state' => $standing['state']])
                            <strong class="collect-amount">
                                <x-rupees :amount="$standing['to_collect'] > 0 ? $standing['to_collect'] : min($loan->installment_amount, $loan->balance())" />
                            </strong>
                        </span>

                        <x-icon name="chevron-right" class="payment-result-arrow" />

                    </a>

                    @if ($loan->customer->phone)
                        <a
                            href="tel:{{ preg_replace('/[^\d+]/', '', $loan->customer->phone) }}"
                            class="member-call-button collect-call-button"
                            aria-label="Call {{ $loan->customer->name }} on {{ $loan->customer->phone }}"
                            title="Call {{ $loan->customer->phone }}"
                        >
                            <x-icon name="phone" />
                            <span data-i18n="call">Call</span>
                        </a>
                    @endif

                </div>
            @endforeach

        @endif

    </section>

</div>

@endsection
