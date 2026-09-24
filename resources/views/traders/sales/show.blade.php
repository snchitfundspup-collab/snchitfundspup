@extends('layouts.app')

@section('title', 'Invoice '.$sale->invoice_number.' | SN Traders')

@push('styles')
    @vite([
        'resources/css/groups.css',
        'resources/css/payments.css',
        'resources/css/traders.css'
    ])
@endpush

@php
    $customer = $sale->customer;
    $paidNow = $sale->paidAtSale();
    $credit = round($sale->total_amount - $paidNow, 2);
    $costed = $sale->items->filter(fn ($item) => $item->cost_rate !== null);
    $profit = round($costed->sum(fn ($item) => $item->profit()), 2);
    $uncosted = $sale->items->reject(fn ($item) => $item->cost_rate !== null)->map(fn ($item) => $item->variety->name)->unique();
@endphp

@section('content')

<div class="group-show-page payments-page traders-page">

    <div class="no-print">
        @include('traders.partials.flash')
    </div>


    <div class="receipt-actions no-print">

        <a href="{{ route('traders.sales.create') }}" class="add-group-button">
            <x-icon name="plus" />
            <span data-i18n="new_sale">New Sale</span>
        </a>

        <button type="button" class="group-action" onclick="window.print()">
            <x-icon name="printer" />
            <span data-i18n="print_receipt">Print</span>
        </button>

        <a href="{{ route('traders.sales.pdf', $sale) }}" class="group-action download-pdf-button">
            <x-icon name="download" />
            <span data-i18n="download_pdf">Download PDF</span>
        </a>

        <a href="{{ route('traders.accounts.show', $customer) }}" class="group-action">
            <x-icon name="user-check" />
            <span data-i18n="customer_account">Customer account</span>
        </a>

        <form
            method="POST"
            action="{{ route('traders.sales.destroy', $sale) }}"
            onsubmit="return confirm('Delete invoice {{ $sale->invoice_number }}? Any money taken with it is removed too.')"
        >
            @csrf
            @method('DELETE')
            <button type="submit" class="group-action receipt-cancel">
                <x-icon name="trash" />
                <span data-i18n="delete_invoice">Delete invoice</span>
            </button>
        </form>

    </div>


    {{-- for the shop only: never on the printed invoice --}}
    <p class="sale-profit-note no-print">
        @if ($costed->isNotEmpty())
            <span data-i18n="profit_on_sale">Profit on this sale</span>
            <strong @class(['is-loss' => $profit < 0])><x-rupees :amount="$profit" /></strong>
            <small>(<span data-i18n="cost_word">cost</span> <x-rupees :amount="$costed->sum(fn ($item) => $item->bags * $item->cost_rate)" />)</small>
        @endif
        @if ($uncosted->isNotEmpty())
            <small><span data-i18n="no_purchase_price_for">No purchase price was set for</span> {{ $uncosted->implode(', ') }}.</small>
        @endif
    </p>


    @include('traders.partials.bill-card', [
        'title' => 'Invoice',
        'numberLabel' => 'Invoice No.',
        'number' => $sale->invoice_number,
        'date' => $sale->sold_on->format('d M Y'),
        'partyRows' => [
            ['Customer', e($customer->name).(filled($customer->remarks) ? ' <span class="customer-ident">('.e($customer->remarks).')</span>' : '')],
            ['Customer ID', e($customer->customer_code)],
            ['Phone', e($customer->phone ?: '—')],
        ],
        'items' => $sale->items,
        'total' => $sale->total_amount,
        'extraRows' => array_values(array_filter([
            ['Received now', $paidNow, 'receive'],
            $credit > 0 ? ['On credit (this invoice)', $credit, 'due-open'] : null,
            ['Customer owes in total now', $customerBalance, $customerBalance > 0 ? 'due-pending' : 'receive'],
        ])),
        'recordedBy' => $sale->recorder?->name,
    ])

</div>

@endsection
