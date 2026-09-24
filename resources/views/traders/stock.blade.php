@extends('layouts.app')

@section('title', 'Stock | SN Traders')

@push('styles')
    @vite([
        'resources/css/groups.css',
        'resources/css/payments.css',
        'resources/css/traders.css'
    ])
@endpush

@php
    $kg = fn ($value) => rtrim(rtrim(number_format((float) $value, 2), '0'), '.');
    $largest = max(1, $rows->max('stock_bags') ?? 1);
@endphp

@section('content')

<div class="groups-list-page payments-page traders-page">

    <div class="groups-list-header">
        <div>
            <h1 class="groups-title" data-i18n="menu_stock">Stock</h1>
            <p class="groups-subtitle">
                <span data-i18n="stock_subtitle">Rice on hand — everything bought minus everything sold — as on</span>
                {{ $today->format('D, d M Y') }}.
            </p>
        </div>
        <div class="ledger-actions">
            <a href="{{ route('traders.stock.print') }}" class="group-action" target="_blank" rel="noopener">
                <x-icon name="printer" />
                <span data-i18n="print_report">Print report</span>
            </a>
            <a href="{{ route('traders.stock.pdf') }}" class="add-group-button">
                <x-icon name="download" />
                <span data-i18n="download_pdf">Download PDF</span>
            </a>
        </div>
    </div>


    <section class="payment-summary-bar glass">
        <div class="payment-summary-main">
            <span class="payment-summary-range" data-i18n="rice_in_stock">Rice in stock</span>
            <strong class="payment-summary-total">{{ $totalBags }} <span data-i18n="bags_lower">bags</span></strong>
            <span class="payment-summary-count">{{ $rows->count() }} <span data-i18n="varieties_word">varieties</span></span>
        </div>
        <div class="payment-summary-actions">
            <a href="{{ route('traders.purchases.create') }}" class="group-action">
                <x-icon name="plus" />
                <span data-i18n="new_purchase">New Purchase</span>
            </a>
            <a href="{{ route('traders.sales.create') }}" class="add-group-button">
                <x-icon name="plus" />
                <span data-i18n="new_sale">New Sale</span>
            </a>
        </div>
    </section>


    @if ($rows->isEmpty())

        <div class="groups-empty glass">
            <span class="groups-empty-icon icon-3d icon-3d-purple"><x-icon name="layers" /></span>
            <strong data-i18n="no_varieties">No rice varieties yet</strong>
            <a href="{{ route('traders.varieties.index') }}" class="add-group-button">
                <x-icon name="plus" />
                <span data-i18n="rice_varieties">Rice Varieties</span>
            </a>
        </div>

    @else

        <section class="group-panel glass">
            <div class="ledger-table-wrapper">
                <table class="ledger-table statement-table">
                    <thead>
                        <tr>
                            <th data-i18n="rice_variety">Rice variety</th>
                            <th class="ledger-col-total" data-i18n="bought_bags">Bought (bags)</th>
                            <th class="ledger-col-total" data-i18n="sold_bags">Sold (bags)</th>
                            <th class="ledger-col-total" data-i18n="in_stock_bags">In stock (bags)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rows as $row)
                            <tr>
                                <td>
                                    <strong>{{ $row['name'] }}</strong>
                                    <small class="dues-part-paid">{{ $kg($row['bag_kg']) }} <span data-i18n="kg_bag">kg bag</span></small>
                                    <div class="stock-bar" aria-hidden="true">
                                        <span style="width: {{ max(0, min(100, $row['stock_bags'] / $largest * 100)) }}%"></span>
                                    </div>
                                </td>
                                <td class="ledger-col-total">{{ $row['purchased_bags'] }}</td>
                                <td class="ledger-col-total">{{ $row['sold_bags'] }}</td>
                                <td @class(['ledger-col-total', 'stock-negative' => $row['stock_bags'] < 0])><strong>{{ $row['stock_bags'] }}</strong></td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <th data-i18n="total">Total</th>
                            <td class="ledger-col-total">{{ $rows->sum('purchased_bags') }}</td>
                            <td class="ledger-col-total">{{ $rows->sum('sold_bags') }}</td>
                            <td class="ledger-col-total"><strong>{{ $totalBags }}</strong></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </section>

    @endif

</div>

@endsection
