@extends('layouts.app')

@section('title', 'Receipts | SN Traders')

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

@php
    $keep = array_filter(['q' => $filters['q']]);
@endphp

@section('content')

<div class="groups-list-page payments-page traders-page">

    @include('traders.partials.flash')


    <div class="groups-list-header">
        <div>
            <h1 class="groups-title" data-i18n="all_receipts">All Receipts</h1>
            <p class="groups-subtitle" data-i18n="receipts_subtitle">Money received from customers — at the sale and later. This month by default.</p>
        </div>
        <a href="{{ route('traders.receipts.create') }}" class="add-group-button">
            <x-icon name="rupee" />
            <span data-i18n="receive_payment">Receive Payment</span>
        </a>
    </div>


    @include('traders.partials.list-filters', [
        'route' => 'traders.receipts.index',
        'searchPlaceholder' => 'Receipt no. or customer',
    ])


    <div id="paymentsResults">

        <section class="payment-summary-bar glass">
            <div class="payment-summary-main">
                <span class="payment-summary-range">@include('traders.partials.range-label')</span>
                <strong class="payment-summary-total"><x-rupees :amount="$total" /></strong>
                <span class="payment-summary-count">{{ $count }} <span data-i18n="receipts">receipts</span></span>
            </div>
        </section>

        @if ($receipts->isEmpty())

            <div class="groups-empty glass">
                <span class="groups-empty-icon icon-3d icon-3d-green"><x-icon name="rupee" /></span>
                <strong data-i18n="no_receipts">No receipts in this period</strong>
            </div>

        @else

            <div class="payment-list">
                @foreach ($receipts as $receipt)
                    <a href="{{ route('traders.receipts.show', $receipt) }}" class="payment-row glass">
                        <x-customer-avatar :customer="$receipt->customer" />
                        <div class="payment-row-main">
                            <strong><x-customer-name :customer="$receipt->customer" /></strong>
                            <span>{{ $receipt->customer->customer_code }}{{ $receipt->sale ? ' · at '.$receipt->sale->invoice_number : ' · against credit' }}</span>
                        </div>
                        <div class="payment-row-meta">
                            <span class="payment-method-tag">{{ $receipt->methodLabel() }}</span>
                            <span>{{ $receipt->receipt_number }} · {{ $receipt->received_at->format('d M Y, h:i A') }}</span>
                        </div>
                        <strong class="payment-row-amount"><x-rupees :amount="$receipt->amount" /></strong>
                    </a>
                @endforeach
            </div>

            <x-pagination :paginator="$receipts" label="Receipt pages" />

        @endif

    </div>

</div>

@endsection
