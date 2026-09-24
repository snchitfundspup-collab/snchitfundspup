@extends('layouts.app')

@section('title', 'Sales | SN Traders')

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
    $dates = ['from' => $filters['from'], 'to' => $filters['to']];
@endphp

@section('content')

<div class="groups-list-page payments-page traders-page">

    @include('traders.partials.flash')


    <div class="groups-list-header">
        <div>
            <h1 class="groups-title" data-i18n="all_sales">All Sales</h1>
            <p class="groups-subtitle" data-i18n="sales_subtitle">Rice sold to customers. This month by default.</p>
        </div>
        <a href="{{ route('traders.sales.create') }}" class="add-group-button">
            <x-icon name="plus" />
            <span data-i18n="new_sale">New Sale</span>
        </a>
    </div>


    @include('traders.partials.list-filters', [
        'route' => 'traders.sales.index',
        'searchPlaceholder' => 'Invoice no. or customer',
    ])


    <div id="paymentsResults">

        <section class="payment-summary-bar glass">

            <div class="payment-summary-main">
                <span class="payment-summary-range">
                    @include('traders.partials.range-label')
                </span>
                <strong class="payment-summary-total"><x-rupees :amount="$summary['total']" /></strong>
                <span class="payment-summary-count">
                    {{ $summary['count'] }} <span data-i18n="{{ $summary['count'] === 1 ? 'invoice_word' : 'invoices_word' }}">{{ $summary['count'] === 1 ? 'invoice' : 'invoices' }}</span>
                    · {{ $summary['bags'] }} <span data-i18n="bags_lower">bags</span>
                </span>
            </div>

            <div class="payment-summary-methods">
                <span class="payment-method-total">
                    <span data-i18n="received_at_sale">Received at sale</span>
                    <strong><x-rupees :amount="$summary['received']" /></strong>
                </span>
                @foreach ($summary['by_variety'] as $byVariety)
                    <span class="payment-method-total">
                        {{ $byVariety['name'] }}
                        <strong>{{ \App\Models\RiceVariety::bagsLabel($byVariety['bags']) }}</strong>
                        <small>(<x-rupees :amount="$byVariety['amount']" />)</small>
                    </span>
                @endforeach
            </div>

            @if ($summary['count'] > 0)
                <div class="payment-summary-actions">
                    <a href="{{ route('traders.sales.print', $keep + $dates) }}" class="group-action" target="_blank" rel="noopener">
                        <x-icon name="printer" />
                        <span data-i18n="print_list">Print list</span>
                    </a>
                    <a href="{{ route('traders.sales.list-pdf', $keep + $dates) }}" class="add-group-button">
                        <x-icon name="download" />
                        <span data-i18n="download_pdf">Download PDF</span>
                    </a>
                </div>
            @endif

        </section>


        @if ($sales->isEmpty())

            <div class="groups-empty glass">
                <span class="groups-empty-icon icon-3d icon-3d-green"><x-icon name="rupee" /></span>
                <strong data-i18n="no_sales">No sales in this period</strong>
                <a href="{{ route('traders.sales.create') }}" class="add-group-button">
                    <x-icon name="plus" />
                    <span data-i18n="new_sale">New Sale</span>
                </a>
            </div>

        @else

            <div class="payment-list">
                @foreach ($sales as $sale)
                    @php $credit = round($sale->total_amount - $sale->receipts->sum('amount'), 2); @endphp
                    <a href="{{ route('traders.sales.show', $sale) }}" class="payment-row glass">
                        <x-customer-avatar :customer="$sale->customer" />
                        <div class="payment-row-main">
                            <strong><x-customer-name :customer="$sale->customer" /></strong>
                            <span>{{ $sale->items->map(fn ($item) => $item->variety->name.' '.$item->quantityLabel())->implode(', ') }}</span>
                        </div>
                        <div class="payment-row-meta">
                            @if ($credit > 0)
                                <span class="due-badge due-badge-month"><x-rupees :amount="$credit" /> <span data-i18n="on_credit">on credit</span></span>
                            @else
                                <span class="due-badge due-badge-ok" data-i18n="paid_word">Paid</span>
                            @endif
                            <span>{{ $sale->invoice_number }} · {{ $sale->sold_on->format('d M Y') }}</span>
                        </div>
                        <strong class="payment-row-amount"><x-rupees :amount="$sale->total_amount" /></strong>
                    </a>
                @endforeach
            </div>

            <x-pagination :paginator="$sales" label="Sale pages" />

        @endif

    </div>

</div>

@endsection
