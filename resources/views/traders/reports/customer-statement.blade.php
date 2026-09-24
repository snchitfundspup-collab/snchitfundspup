@extends('layouts.app')

@section('title', 'Customer Statement | SN Traders')

@push('styles')
    @vite([
        'resources/css/groups.css',
        'resources/css/payments.css',
        'resources/css/traders.css'
    ])
@endpush

@push('scripts')
    @vite('resources/js/payments.js')
@endpush

@section('content')

<div class="groups-list-page payments-page traders-page">

    <div class="groups-list-header">
        <div>
            <h1 class="groups-title" data-i18n="customer_statement">Customer Statement</h1>
            <p class="groups-subtitle" data-i18n="traders_statement_subtitle">Find a customer to see every invoice and payment with the running balance — print or download it.</p>
        </div>
    </div>


    <form
        method="GET"
        action="{{ route('traders.reports.customer') }}"
        class="payment-filters payment-filters-grid"
        id="paymentFilterForm"
        role="search"
    >
        <div class="member-search payment-filter-search">
            <span class="member-search-icon"><x-icon name="search" /></span>
            <input type="search" name="q" class="member-search-input" value="{{ $search }}" placeholder="Name, ID, phone or identification" autocomplete="off" autofocus>
        </div>

        <a
            href="{{ route('traders.reports.customer') }}"
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
                <strong data-i18n="no_trading_customers">No customer found who has bought rice.</strong>
            </div>

        @else

            @if ($search === '')
                <h3 class="draw-list-title" data-i18n="recent_customers">Customers who bought most recently</h3>
            @endif

            <div class="payment-list">
                @foreach ($customers as $customer)
                    @php $balance = (float) ($balances[$customer->id] ?? 0); @endphp
                    <a href="{{ route('traders.accounts.show', $customer) }}" class="payment-row glass">
                        <x-customer-avatar :customer="$customer" />
                        <div class="payment-row-main">
                            <strong><x-customer-name :customer="$customer" /></strong>
                            <span>{{ $customer->customer_code }} · {{ $customer->phone ?: '—' }}</span>
                        </div>
                        <div class="payment-row-meta">
                            @if ($balance >= 0.01)
                                <span class="due-badge due-badge-due"><x-rupees :amount="$balance" /> <span data-i18n="owes_word">owes</span></span>
                            @elseif ($balance <= -0.01)
                                <span class="due-badge due-badge-done"><x-rupees :amount="-$balance" /> <span data-i18n="advance_word">advance</span></span>
                            @else
                                <span class="due-badge due-badge-ok" data-i18n="settled_word">Settled</span>
                            @endif
                            @if ($customer->last_sale)
                                <span><span data-i18n="last_sale">Last sale</span> {{ \Illuminate\Support\Carbon::parse($customer->last_sale)->format('d M Y') }}</span>
                            @endif
                        </div>
                        <span class="payment-row-amount"><x-icon name="chevron-right" /></span>
                    </a>
                @endforeach
            </div>

        @endif

    </div>

</div>

@endsection
