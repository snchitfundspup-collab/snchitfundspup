@extends('layouts.app')

@section('title', 'Purchases | SN Traders')

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
    $keep = array_filter(['q' => $filters['q'], 'supplier' => $filters['supplier']]);
    $dates = ['from' => $filters['from'], 'to' => $filters['to']];

    $supplierFilter = view('traders.partials.select-filter', [
        'name' => 'supplier',
        'label' => 'Supplier',
        'i18n' => 'supplier_word',
        'allLabel' => 'All suppliers',
        'options' => $suppliers->pluck('name', 'id'),
        'selected' => $filters['supplier'],
    ])->render();
@endphp

@section('content')

<div class="groups-list-page payments-page traders-page">

    @include('traders.partials.flash')


    <div class="groups-list-header">
        <div>
            <h1 class="groups-title" data-i18n="all_purchases">All Purchases</h1>
            <p class="groups-subtitle" data-i18n="purchases_subtitle">Rice bought from suppliers. This month by default.</p>
        </div>
        <a href="{{ route('traders.purchases.create') }}" class="add-group-button">
            <x-icon name="plus" />
            <span data-i18n="new_purchase">New Purchase</span>
        </a>
    </div>


    @include('traders.partials.list-filters', [
        'route' => 'traders.purchases.index',
        'searchPlaceholder' => 'Purchase no., supplier or bill no.',
        'extra' => $supplierFilter,
    ])


    <div id="paymentsResults">

        <section class="payment-summary-bar glass">

            <div class="payment-summary-main">
                <span class="payment-summary-range">
                    @include('traders.partials.range-label')
                    @if ($filters['supplier_name']) · {{ $filters['supplier_name'] }} @endif
                </span>
                <strong class="payment-summary-total expense-total"><x-rupees :amount="$summary['total']" /></strong>
                <span class="payment-summary-count">
                    {{ $summary['count'] }} <span data-i18n="purchases_word">purchases</span>
                    · {{ $summary['bags'] }} <span data-i18n="bags_lower">bags</span>
                </span>
            </div>

            <div class="payment-summary-methods">
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
                    <a href="{{ route('traders.purchases.print', $keep + $dates) }}" class="group-action" target="_blank" rel="noopener">
                        <x-icon name="printer" />
                        <span data-i18n="print_list">Print list</span>
                    </a>
                    <a href="{{ route('traders.purchases.list-pdf', $keep + $dates) }}" class="add-group-button">
                        <x-icon name="download" />
                        <span data-i18n="download_pdf">Download PDF</span>
                    </a>
                </div>
            @endif

        </section>


        @if ($purchases->isEmpty())

            <div class="groups-empty glass">
                <span class="groups-empty-icon icon-3d icon-3d-blue"><x-icon name="package" /></span>
                <strong data-i18n="no_purchases">No purchases in this period</strong>
                <a href="{{ route('traders.purchases.create') }}" class="add-group-button">
                    <x-icon name="plus" />
                    <span data-i18n="new_purchase">New Purchase</span>
                </a>
            </div>

        @else

            <div class="payment-list">
                @foreach ($purchases as $purchase)
                    <a href="{{ route('traders.purchases.show', $purchase) }}" class="payment-row glass">
                        <span class="draw-row-icon icon-3d icon-3d-blue"><x-icon name="package" /></span>
                        <div class="payment-row-main">
                            <strong>{{ $purchase->supplier->name }}</strong>
                            <span>{{ $purchase->items->map(fn ($item) => $item->variety->name.' '.$item->quantityLabel())->implode(', ') }}</span>
                        </div>
                        <div class="payment-row-meta">
                            <span class="payment-method-tag">{{ $purchase->methodLabel() }}</span>
                            <span>{{ $purchase->purchase_number }}{{ $purchase->supplier_bill_no ? ' · Bill '.$purchase->supplier_bill_no : '' }} · {{ $purchase->purchased_on->format('d M Y') }}</span>
                        </div>
                        <strong class="payment-row-amount"><x-rupees :amount="$purchase->total_amount" /></strong>
                    </a>
                @endforeach
            </div>

            <x-pagination :paginator="$purchases" label="Purchase pages" />

        @endif

    </div>

</div>

@endsection
