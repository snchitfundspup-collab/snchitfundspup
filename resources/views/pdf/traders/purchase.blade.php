@extends('pdf.layout')

@section('title', 'Purchase '.$purchase->purchase_number.'')

@section('page_margin', '10mm')

@section('font_size', '10px')

@section('doc_title', 'Purchase')

@section('doc_subtitle', $purchase->purchase_number)

@section('styles')
    @include('pdf.traders.partials.bill-styles')
@endsection

@section('content')

    @include('pdf.traders.partials.bill', [
        'numberLabel' => 'Purchase No.',
        'number' => $purchase->purchase_number,
        'date' => $purchase->purchased_on->format('d M Y'),
        'partyRows' => array_values(array_filter([
            ['Supplier', $purchase->supplier->name],
            $purchase->supplier->place ? ['Place', $purchase->supplier->place] : null,
            $purchase->supplier->phone ? ['Phone', $purchase->supplier->phone] : null,
            $purchase->supplier_bill_no ? ["Supplier's bill no.", $purchase->supplier_bill_no] : null,
            ['Payment method', $purchase->methodLabel()],
            $purchase->notes ? ['Notes', $purchase->notes] : null,
        ])),
        'items' => $purchase->items,
        'total' => $purchase->total_amount,
        'extraRows' => [],
        'recordedBy' => $purchase->recorder?->name,
    ])

@endsection
