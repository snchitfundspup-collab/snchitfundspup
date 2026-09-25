@extends('layouts.portal')

@section('title', 'My Orders')

@php
    $badges = [
        'new' => ['due-badge-month', 'order_waiting', 'Waiting'],
        'completed' => ['due-badge-ok', 'completed_word', 'Completed'],
        'cancelled' => ['due-badge-upcoming', 'cancelled_word', 'Cancelled'],
    ];
@endphp

@section('content')

<div class="groups-list-page payments-page portal-page">

    <div class="groups-list-header">
        <div>
            @include('portal.partials.back')
            <h1 class="groups-title" data-i18n="my_orders">My Orders</h1>
            <p class="groups-subtitle" data-i18n="my_orders_subtitle">Rice you ordered. Once the office makes the bill, it appears in Bills &amp; Statement.</p>
        </div>
        <div class="ledger-actions">
            @include('portal.partials.call-office')
            <a href="{{ route('portal.rice') }}" class="add-group-button">
                <x-icon name="plus" />
                <span data-i18n="order_rice">Order rice</span>
            </a>
        </div>
    </div>

    @if ($orders->isEmpty())

        <div class="groups-empty glass">
            <span class="groups-empty-icon icon-3d icon-3d-green"><x-icon name="package" /></span>
            <strong data-i18n="no_orders_yet">You have not ordered rice yet.</strong>
        </div>

    @else

        <div class="payment-list">
            @foreach ($orders as $order)
                @php [$badgeClass, $badgeKey, $badgeLabel] = $badges[$order->status] ?? $badges['new']; @endphp
                <div class="payment-row glass portal-order-row">
                    <span class="portal-row-icon icon-3d icon-3d-green"><x-icon name="package" /></span>
                    <div class="payment-row-main">
                        <strong>{{ $order->order_number }}</strong>
                        <span>{{ $order->itemsLabel() }}</span>
                        @if ($order->notes)
                            <small>“{{ $order->notes }}”</small>
                        @endif
                    </div>
                    <div class="payment-row-meta">
                        <span class="due-badge {{ $badgeClass }}" data-i18n="{{ $badgeKey }}">{{ $badgeLabel }}</span>
                        <span>{{ $order->created_at->timezone(config('app.business_timezone'))->format('d M Y, h:i A') }}</span>
                        @if ($order->sale)
                            <a href="{{ route('portal.bills.invoice.pdf', $order->sale) }}" class="statement-receipt-link">
                                <span data-i18n="invoice_word_title">Invoice</span> {{ $order->sale->invoice_number }}
                            </a>
                        @endif
                    </div>
                    <div class="portal-order-side">
                        <strong class="payment-row-amount"><x-rupees :amount="$order->sale?->total_amount ?? $order->estimated_total" /></strong>
                        @if ($order->isNew())
                            <form method="POST" action="{{ route('portal.orders.cancel', $order) }}" onsubmit="return confirm('Cancel order {{ $order->order_number }}?')">
                                @csrf
                                <button type="submit" class="group-action portal-cancel-button">
                                    <x-icon name="x" />
                                    <span data-i18n="cancel_order">Cancel</span>
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        <x-pagination :paginator="$orders" label="Order pages" />

    @endif

</div>

@endsection
