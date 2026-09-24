@extends('layouts.app')

@section('title', 'Purchase '.$purchase->purchase_number.' | SN Traders')

@push('styles')
    @vite([
        'resources/css/groups.css',
        'resources/css/payments.css',
        'resources/css/traders.css'
    ])
@endpush

@section('content')

<div class="group-show-page payments-page traders-page">

    <div class="no-print">
        @include('traders.partials.flash')
    </div>


    <div class="receipt-actions no-print">

        <a href="{{ route('traders.purchases.create') }}" class="add-group-button">
            <x-icon name="plus" />
            <span data-i18n="new_purchase">New Purchase</span>
        </a>

        <button type="button" class="group-action" onclick="window.print()">
            <x-icon name="printer" />
            <span data-i18n="print_receipt">Print</span>
        </button>

        <a href="{{ route('traders.purchases.pdf', $purchase) }}" class="group-action download-pdf-button">
            <x-icon name="download" />
            <span data-i18n="download_pdf">Download PDF</span>
        </a>

        <form
            method="POST"
            action="{{ route('traders.purchases.destroy', $purchase) }}"
            onsubmit="return confirm('Delete purchase {{ $purchase->purchase_number }}? Its rice comes off the stock.')"
        >
            @csrf
            @method('DELETE')
            <button type="submit" class="group-action receipt-cancel">
                <x-icon name="trash" />
                <span data-i18n="delete_purchase">Delete purchase</span>
            </button>
        </form>

    </div>


    @include('traders.partials.bill-card', [
        'title' => 'Purchase',
        'numberLabel' => 'Purchase No.',
        'number' => $purchase->purchase_number,
        'date' => $purchase->purchased_on->format('d M Y'),
        'partyRows' => array_values(array_filter([
            ['Supplier', e($purchase->supplier->name)],
            $purchase->supplier->place ? ['Place', e($purchase->supplier->place)] : null,
            $purchase->supplier->phone ? ['Phone', e($purchase->supplier->phone)] : null,
            $purchase->supplier_bill_no ? ["Supplier's bill no.", e($purchase->supplier_bill_no)] : null,
            ['Payment method', e($purchase->methodLabel())],
            $purchase->notes ? ['Notes', e($purchase->notes)] : null,
        ])),
        'items' => $purchase->items,
        'total' => $purchase->total_amount,
        'extraRows' => [],
        'recordedBy' => $purchase->recorder?->name,
        'thanks' => 'Purchase record — SN Traders',
    ])

</div>

@endsection
