@extends('layouts.app')

@section('title', 'Customer Statement | Sri Lakshmi Micro Finance')

@push('styles')
    @vite([
        'resources/css/groups.css',
        'resources/css/payments.css',
        'resources/css/traders.css',
        'resources/css/finance.css'
    ])
@endpush

@push('scripts')
    @vite('resources/js/payments.js')
@endpush

@section('content')

<div class="groups-list-page payments-page traders-page finance-page">

    <div class="groups-list-header">
        <div>
            <h1 class="groups-title" data-i18n="customer_statement">Customer Statement</h1>
            <p class="groups-subtitle" data-i18n="finance_statement_subtitle">Find a customer to see every loan and collection — print or download it.</p>
        </div>
    </div>


    <form
        method="GET"
        action="{{ route('finance.reports.customer') }}"
        class="payment-filters payment-filters-grid"
        id="paymentFilterForm"
        role="search"
    >
        <div class="member-search payment-filter-search">
            <span class="member-search-icon"><x-icon name="search" /></span>
            <input type="search" name="q" class="member-search-input" value="{{ $search }}" placeholder="Name, ID, phone or identification" autocomplete="off" autofocus>
        </div>

        <a
            href="{{ route('finance.reports.customer') }}"
            class="group-action"
            id="paymentFilterClear"
            @if ($search === '') hidden @endif
        >
            <x-icon name="x" />
            <span data-i18n="clear">Clear</span>
        </a>
    </form>


    <div id="paymentsResults">

        @if ($customers->isEmpty())

            <div class="groups-empty glass">
                <span class="groups-empty-icon icon-3d icon-3d-purple"><x-icon name="users" /></span>
                <strong data-i18n="no_loan_customers">No customer found who has had a loan.</strong>
            </div>

        @else

            @if ($search === '')
                <h3 class="draw-list-title" data-i18n="recent_borrowers">Customers with the latest loans</h3>
            @endif

            <div class="payment-list">
                @foreach ($customers as $customer)
                    <a href="{{ route('finance.accounts.show', $customer) }}" class="payment-row glass">
                        <x-customer-avatar :customer="$customer" />
                        <div class="payment-row-main">
                            <strong><x-customer-name :customer="$customer" /></strong>
                            <span>{{ $customer->customer_code }} · {{ $customer->phone ?: '—' }}</span>
                        </div>
                        <div class="payment-row-meta">
                            @if ($customer->running_loans > 0)
                                <span class="due-badge due-badge-done">{{ $customer->running_loans }} <span data-i18n="running_loans">running loans</span></span>
                            @else
                                <span class="due-badge due-badge-ok" data-i18n="all_closed">All closed</span>
                            @endif
                            <span><span data-i18n="last_loan">Last loan</span> {{ \Illuminate\Support\Carbon::parse($customer->last_loan)->format('d M Y') }}</span>
                        </div>
                        <span class="payment-row-amount"><x-icon name="chevron-right" /></span>
                    </a>
                @endforeach
            </div>

        @endif

    </div>

</div>

@endsection
