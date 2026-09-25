@extends('layouts.app')

@section('title', 'Customer Orders | SN Traders')

@push('styles')
    @vite([
        'resources/css/groups.css',
        'resources/css/payments.css',
        'resources/css/traders.css'
    ])
@endpush

@php
    $tabs = [
        'new' => ['orders_new', 'New'],
        'completed' => ['completed_word', 'Completed'],
        'cancelled' => ['cancelled_word', 'Cancelled'],
    ];
@endphp

@section('content')

<div class="groups-list-page payments-page traders-page">

    @include('traders.partials.flash')


    <div class="groups-list-header">
        <div>
            <h1 class="groups-title" data-i18n="customer_orders">Customer Orders</h1>
            <p class="groups-subtitle" data-i18n="customer_orders_subtitle">Rice customers ordered from their own pages. Make the sale to send the bill, or cancel the order.</p>
        </div>
    </div>


    <nav class="status-tabs collect-tabs" aria-label="Order status">
        @foreach ($tabs as $tabStatus => [$tabKey, $tabLabel])
            <a
                href="{{ route('traders.orders.index', $tabStatus === 'new' ? [] : ['status' => $tabStatus]) }}"
                @class(['status-tab', 'active' => $status === $tabStatus])
            >
                <span data-i18n="{{ $tabKey }}">{{ $tabLabel }}</span>
                <span class="status-tab-count">{{ $counts[$tabStatus] ?? 0 }}</span>
            </a>
        @endforeach
    </nav>


    @if ($orders->isEmpty())

        <div class="groups-empty glass">
            <span class="groups-empty-icon icon-3d icon-3d-green"><x-icon name="check" /></span>
            <strong data-i18n="no_orders_here">No orders here.</strong>
        </div>

    @else

        <div class="payment-list">
            @foreach ($orders as $order)
                <div class="payment-row glass traders-order-row">
                    <x-customer-avatar :customer="$order->customer" />
                    <div class="payment-row-main">
                        <strong><x-customer-name :customer="$order->customer" /></strong>
                        <span>{{ $order->itemsLabel() }}</span>
                        @if ($order->notes)
                            <small>“{{ $order->notes }}”</small>
                        @endif
                    </div>
                    <div class="payment-row-meta">
                        <span>{{ $order->order_number }} · {{ $order->created_at->timezone(config('app.business_timezone'))->format('d M Y, h:i A') }}</span>
                        <span>{{ $order->customer->customer_code }} · {{ $order->customer->phone ?: '—' }}</span>
                        @if ($order->sale)
                            <a href="{{ route('traders.sales.show', $order->sale) }}" class="statement-receipt-link">
                                <span data-i18n="invoice_word_title">Invoice</span> {{ $order->sale->invoice_number }}
                            </a>
                        @endif
                    </div>
                    <strong class="payment-row-amount"><x-rupees :amount="$order->sale?->total_amount ?? $order->estimated_total" /></strong>
                    @if ($order->isNew())
                        <div class="master-actions traders-order-actions">
                            <a href="{{ route('traders.sales.create', ['order' => $order->id]) }}" class="add-group-button">
                                <x-icon name="rupee" />
                                <span data-i18n="make_sale">Make sale</span>
                            </a>
                            @if ($order->customer->phone)
                                <a href="tel:{{ preg_replace('/[^\d+]/', '', $order->customer->phone) }}" class="member-call-button collect-call-button" aria-label="Call {{ $order->customer->name }}">
                                    <x-icon name="phone" />
                                    <span data-i18n="call">Call</span>
                                </a>
                            @endif
                            <form method="POST" action="{{ route('traders.orders.cancel', $order) }}" onsubmit="return confirm('Cancel order {{ $order->order_number }}?')">
                                @csrf
                                <button type="submit" class="balance-delete" title="Cancel order" aria-label="Cancel order {{ $order->order_number }}"><x-icon name="x" /></button>
                            </form>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>

        <x-pagination :paginator="$orders" label="Order pages" />

    @endif

</div>

@endsection
