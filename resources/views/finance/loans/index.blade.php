@extends('layouts.app')

@section('title', 'Loans | Sri Lakshmi Micro Finance')

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
            <h1 class="groups-title" data-i18n="all_loans">All Loans</h1>
            <p class="groups-subtitle" data-i18n="all_loans_subtitle">Every loan given, with what is collected and what is left.</p>
        </div>
        <div class="ledger-actions">
            <a href="{{ route('finance.loans.create') }}" class="add-group-button">
                <x-icon name="plus" />
                <span data-i18n="new_loan">New Loan</span>
            </a>
        </div>
    </div>


    <nav class="status-tabs" aria-label="Loans">
        @foreach (['active' => ['running_loans_tab', 'Running', $counts['active']], 'closed' => ['closed_word', 'Closed', $counts['closed']], 'all' => ['status_all', 'All', null]] as $tabStatus => [$tabKey, $tabLabel, $tabCount])
            <a
                href="{{ route('finance.loans.index', array_filter(['status' => $tabStatus === 'active' ? null : $tabStatus, 'q' => $search])) }}"
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


    <form method="GET" action="{{ route('finance.loans.index') }}" class="payment-filters" role="search">
        @if ($status !== 'active')
            <input type="hidden" name="status" value="{{ $status }}">
        @endif
        <div class="member-search payment-filter-search">
            <span class="member-search-icon"><x-icon name="search" /></span>
            <input type="search" name="q" class="member-search-input" value="{{ $search }}" placeholder="Search name, ID, phone or loan number" autocomplete="off">
        </div>
    </form>


    @if ($loans->isEmpty())

        <div class="groups-empty glass">
            <span class="groups-empty-icon icon-3d icon-3d-green"><x-icon name="wallet" /></span>
            <strong data-i18n="no_loans">No loans here.</strong>
            <a href="{{ route('finance.loans.create') }}" class="add-group-button">
                <x-icon name="plus" />
                <span data-i18n="new_loan">New Loan</span>
            </a>
        </div>

    @else

        <div class="payment-list">
            @foreach ($loans as $loan)
                @php $standing = $loan->standing(); @endphp
                <a href="{{ route('finance.loans.show', $loan) }}" class="payment-row glass finance-loan-row">
                    <x-customer-avatar :customer="$loan->customer" />
                    <div class="payment-row-main">
                        <strong><x-customer-name :customer="$loan->customer" /></strong>
                        <span>{{ $loan->loan_number }} · {{ $loan->customer->customer_code }} · {{ $loan->loaned_on->format('d M Y') }}</span>
                        <small>{{ $loan->frequencyLabel() }} <x-rupees :amount="$loan->installment_amount" /> · {{ $standing['installments_paid'] }} / {{ $loan->installments }}</small>
                    </div>
                    <div class="payment-row-meta">
                        @include('finance.partials.state-badge', ['state' => $standing['state']])
                        @if ($standing['overdue'] > 0)
                            <span class="ledger-total-due"><span data-i18n="overdue_word">Overdue</span> <x-rupees :amount="$standing['overdue']" /></span>
                        @endif
                    </div>
                    <div class="finance-row-amounts">
                        <strong class="payment-row-amount"><x-rupees :amount="$loan->balance()" /></strong>
                        <small><span data-i18n="of_word">of</span> <x-rupees :amount="$loan->loan_amount" /></small>
                    </div>
                </a>
            @endforeach
        </div>

        <x-pagination :paginator="$loans" label="Loan pages" />

    @endif

</div>

@endsection
