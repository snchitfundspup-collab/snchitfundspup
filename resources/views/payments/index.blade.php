@extends('layouts.app')

@section('title', 'Payments | SN Chit Funds')

@push('styles')
    @vite([
        'resources/css/groups.css',
        'resources/css/payments.css'
    ])
@endpush

@section('content')

<div class="groups-list-page payments-page">


    {{-- =====================================================
         HEADER
    ====================================================== --}}

    <div class="groups-list-header">

        <div>

            <div class="groups-title-row">
                <h1
                    class="groups-title"
                    data-i18n="menu_payments"
                >
                    Payments
                </h1>
            </div>

            <p class="groups-subtitle">
                <span data-i18n="collected_today">Collected today</span>:
                <strong class="today-total"><x-rupees :amount="$todayTotal" /></strong>
                ({{ $todayCount }} <span data-i18n="receipts">receipts</span>)
            </p>

        </div>

        <a
            href="{{ route('payments.create') }}"
            class="add-group-button"
        >
            <x-icon name="rupee" />
            <span data-i18n="collect_payment">Collect Payment</span>
        </a>

    </div>


    {{-- =====================================================
         FILTERS
    ====================================================== --}}

    <form
        method="GET"
        action="{{ route('payments.index') }}"
        class="payment-filters"
        id="paymentFilterForm"
        role="search"
    >

        <div class="member-search">

            <span class="member-search-icon">
                <x-icon name="search" />
            </span>

            <input
                type="search"
                name="q"
                class="member-search-input"
                value="{{ $search }}"
                placeholder="Receipt no., customer or group"
                data-i18n-placeholder="payment_filter_placeholder"
                autocomplete="off"
            >

        </div>

        <select
            name="method"
            class="input payment-filter-select"
            aria-label="Payment method"
        >
            <option value="" data-i18n="all_methods">All methods</option>
            @foreach (\App\Models\Payment::METHODS as $methodValue => $methodLabel)
                <option
                    value="{{ $methodValue }}"
                    @selected($method === $methodValue)
                    data-i18n="method_{{ $methodValue }}"
                >{{ $methodLabel }}</option>
            @endforeach
        </select>

        <input
            type="date"
            name="date"
            class="input payment-filter-select"
            value="{{ $date }}"
            aria-label="Payment date"
        >

        <a
            href="{{ route('payments.index') }}"
            class="group-action"
            id="paymentFilterClear"
            @if ($search === '' && $method === '' && ! $date) hidden @endif
        >
            <x-icon name="x" />
            <span data-i18n="clear">Clear</span>
        </a>

    </form>


    {{-- results are swapped in place by payments.js while filtering --}}

    <div id="paymentsResults">


    {{-- =====================================================
         LIST
    ====================================================== --}}

    @if ($payments->isEmpty())

        <div class="groups-empty glass">

            <span class="groups-empty-icon icon-3d icon-3d-green">
                <x-icon name="rupee" />
            </span>

            <strong data-i18n="no_payments">No payments found</strong>

            <a
                href="{{ route('payments.create') }}"
                class="add-group-button"
            >
                <x-icon name="rupee" />
                <span data-i18n="collect_payment">Collect Payment</span>
            </a>

        </div>

    @else

        <div class="payment-list">

            @foreach ($payments as $payment)

                <a
                    href="{{ route('payments.show', $payment) }}"
                    class="payment-row glass"
                >

                    <x-customer-avatar :customer="$payment->customer" />

                    <div class="payment-row-main">
                        <strong><x-customer-name :customer="$payment->customer" /></strong>
                        <span>
                            {{ $payment->member->member_code }} · {{ $payment->chitGroup->name }}
                            · {{ $payment->monthsCoveredLabel() }}
                        </span>
                    </div>

                    <div class="payment-row-meta">
                        <span class="payment-method-tag">{{ $payment->methodLabel() }}</span>
                        <span>{{ $payment->receipt_number }} · {{ $payment->paid_at->format('d M Y, h:i A') }}</span>
                    </div>

                    <strong class="payment-row-amount">
                        <x-rupees :amount="$payment->amount" />
                    </strong>

                </a>

            @endforeach

        </div>


        <x-pagination :paginator="$payments" label="Payment pages" />

    @endif

    </div>

</div>

@endsection


@push('scripts')
    @vite('resources/js/payments.js')
@endpush
