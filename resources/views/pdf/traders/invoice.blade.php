@extends('pdf.layout')

@section('title', 'Invoice '.$sale->invoice_number.'')

@section('page_margin', '10mm')

@section('font_size', '10px')

@section('doc_title', 'Invoice')

@section('doc_subtitle', $sale->invoice_number)

@section('styles')
    @include('pdf.traders.partials.bill-styles')
@endsection

@section('content')

    @php
        $customer = $sale->customer;
        $paidNow = $sale->paidAtSale();
        $credit = round($sale->total_amount - $paidNow, 2);
    @endphp

    @include('pdf.traders.partials.bill', [
        'numberLabel' => 'Invoice No.',
        'number' => $sale->invoice_number,
        'date' => $sale->sold_on->format('d M Y'),
        'partyRows' => [
            ['Customer', $customer->name.(filled($customer->remarks) ? ' ('.$customer->remarks.')' : '')],
            ['Customer ID', $customer->customer_code],
            ['Phone', $customer->phone ?: '—'],
        ],
        'items' => $sale->items,
        'total' => $sale->total_amount,
        'extraRows' => array_values(array_filter([
            ['Received now', $paidNow],
            $credit > 0 ? ['On credit (this invoice)', $credit] : null,
            ['Customer owes in total now', $customerBalance],
        ])),
        'recordedBy' => $sale->recorder?->name,
    ])

@endsection
